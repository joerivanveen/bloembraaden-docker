FROM postgres:17

# OpenSSL should already be installed in postgres:17 (debian-based)
# but verify and install if needed
RUN apt-get update && \
    apt-get install -y --no-install-recommends openssl && \
    rm -rf /var/lib/apt/lists/*

# Copy certificate generation scripts
COPY ./scripts/postgres-entrypoint.sh /usr/local/bin/postgres-entrypoint.sh
COPY ./scripts/_postgres-certs.sh /usr/local/bin/_postgres-certs.sh

# Make scripts executable
RUN chmod +x /usr/local/bin/postgres-entrypoint.sh && \
    chmod +x /usr/local/bin/_postgres-certs.sh

# Use custom wrapper entrypoint
ENTRYPOINT ["/usr/local/bin/postgres-entrypoint.sh"]
CMD ["postgres"]
