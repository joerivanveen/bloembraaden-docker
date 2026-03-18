#!/usr/bin/env bash

CERT_DIR=/var/lib/postgresql/ssl
CERT_VALIDITY_DAYS=3650  # 10 years
CHECK_EXPIRY_DAYS=30     # Regenerate if expiring within 30 days

generate-postgres-certificates() {
    echo "Checking PostgreSQL SSL certificates in $CERT_DIR..."

    # Ensure certificate directory exists
    mkdir -p "$CERT_DIR"
    cd "$CERT_DIR"

    # Check if force regeneration is requested
    if [ "$POSTGRES_SSL_REGENERATE" = "true" ]; then
        echo "Force regeneration requested, removing existing certificates..."
        rm -f ca.key ca.crt server.key server.crt server.csr dh2048.pem root.crt ca.srl san.cnf
    fi

    # Check if certificates need generation
    if should_generate_certificates; then
        echo "Generating SSL certificates..."
        generate_all_certificates
        set_certificate_permissions
        echo "SSL certificates generated successfully"
    else
        echo "Valid SSL certificates already exist, skipping generation"
        set_certificate_permissions  # Ensure correct permissions
    fi

    # Verify certificates
    verify_certificates || exit 1
}

should_generate_certificates() {
    local required_files=("ca.crt" "ca.key" "server.crt" "server.key" "dh2048.pem" "root.crt")

    # Check if all required files exist
    for file in "${required_files[@]}"; do
        if [ ! -f "$CERT_DIR/$file" ]; then
            echo "Missing certificate file: $file"
            return 0  # Need to generate
        fi
    done

    # Check certificate expiration (regenerate if expiring within 30 days)
    local expiry_seconds=$((86400 * CHECK_EXPIRY_DAYS))

    if ! openssl x509 -checkend "$expiry_seconds" -noout -in "$CERT_DIR/ca.crt" 2>/dev/null; then
        echo "CA certificate is expired or expiring soon"
        return 0  # Need to generate
    fi

    if ! openssl x509 -checkend "$expiry_seconds" -noout -in "$CERT_DIR/server.crt" 2>/dev/null; then
        echo "Server certificate is expired or expiring soon"
        return 0  # Need to generate
    fi

    if [ ! -s "$CERT_DIR/dh2048.pem" ]; then
        echo "DH parameters file is empty or invalid"
        return 0  # Need to generate
    fi

    return 1  # No need to generate
}

generate_all_certificates() {
    # Create temporary directory for atomic operations
    local temp_dir="$CERT_DIR/.tmp"
    mkdir -p "$temp_dir"
    cd "$temp_dir"

    # Generate or reuse CA
    if [ ! -f "$CERT_DIR/ca.crt" ] || [ ! -f "$CERT_DIR/ca.key" ]; then
        echo "Generating CA certificate..."
        generate_ca_certificate
    else
        echo "Reusing existing CA certificate..."
        cp "$CERT_DIR/ca.crt" ca.crt
        cp "$CERT_DIR/ca.key" ca.key
    fi

    # Generate server certificate
    echo "Generating server certificate..."
    generate_server_certificate

    # Generate or reuse DH parameters
    if [ ! -f "$CERT_DIR/dh2048.pem" ] || [ ! -s "$CERT_DIR/dh2048.pem" ]; then
        echo "Generating DH parameters (this may take 30-60 seconds)..."
        generate_dh_parameters
    else
        echo "Reusing existing DH parameters..."
        cp "$CERT_DIR/dh2048.pem" dh2048.pem
    fi

    # Copy root certificate
    cp ca.crt root.crt

    # Move all files to certificate directory atomically
    mv -f ca.crt ca.key server.crt server.key dh2048.pem root.crt "$CERT_DIR/"

    # Cleanup
    cd "$CERT_DIR"
    rm -rf "$temp_dir"
}

generate_ca_certificate() {
    # Generate CA private key (4096-bit RSA)
    openssl genrsa -out ca.key 4096 2>/dev/null

    # Generate self-signed CA certificate (10 years)
    openssl req -new -x509 -days "$CERT_VALIDITY_DAYS" -key ca.key -out ca.crt \
        -subj "/CN=Bloembraaden PostgreSQL CA/O=Bloembraaden/C=NL" \
        2>/dev/null
}

generate_server_certificate() {
    # Generate server private key (2048-bit RSA)
    openssl genrsa -out server.key 2048 2>/dev/null

    # Generate certificate signing request
    openssl req -new -key server.key -out server.csr \
        -subj "/CN=postgres/O=Bloembraaden/C=NL" \
        2>/dev/null

    # Create SAN configuration for Subject Alternative Names
    cat > san.cnf <<'EOF'
[req]
distinguished_name = req_distinguished_name
req_extensions = v3_req

[req_distinguished_name]

[v3_req]
subjectAltName = @alt_names
extendedKeyUsage = serverAuth

[alt_names]
DNS.1 = postgres
DNS.2 = localhost
IP.1 = 127.0.0.1
EOF

    # Sign server certificate with CA (10 years)
    openssl x509 -req -days "$CERT_VALIDITY_DAYS" -in server.csr \
        -CA ca.crt -CAkey ca.key -CAcreateserial \
        -out server.crt -extfile san.cnf -extensions v3_req \
        2>/dev/null

    # Cleanup temporary files
    rm -f server.csr san.cnf ca.srl
}

generate_dh_parameters() {
    openssl dhparam -out dh2048.pem 2048 2>/dev/null
}

set_certificate_permissions() {
    cd "$CERT_DIR"

    # Set ownership to postgres user (UID 999, GID 999)
    chown -R postgres:postgres .

    # Private keys: 600 (only postgres can read)
    chmod 600 ca.key server.key 2>/dev/null || true

    # Certificates and DH parameters: 644 (world-readable)
    chmod 644 ca.crt server.crt root.crt dh2048.pem 2>/dev/null || true

    # Directory: 755
    chmod 755 "$CERT_DIR"
}

verify_certificates() {
    echo ""
    echo "Verifying certificate chain..."

    # Verify server certificate against CA
    if ! openssl verify -CAfile "$CERT_DIR/ca.crt" "$CERT_DIR/server.crt" 2>/dev/null; then
        echo "ERROR: Server certificate verification failed"
        return 1
    fi
    echo "✓ Certificate chain valid"

    # Display certificate validity dates
    echo ""
    echo "CA certificate validity:"
    openssl x509 -in "$CERT_DIR/ca.crt" -noout -dates

    echo ""
    echo "Server certificate validity:"
    openssl x509 -in "$CERT_DIR/server.crt" -noout -dates

    # Display Subject Alternative Names
    echo ""
    echo "Server certificate Subject Alternative Names:"
    openssl x509 -in "$CERT_DIR/server.crt" -noout -text | grep -A 2 "Subject Alternative Name" || echo "  (none found)"

    echo ""
    return 0
}
