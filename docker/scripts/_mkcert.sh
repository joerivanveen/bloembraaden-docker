#!/usr/bin/env bash

make-certificates() {
  if [ ! -f "$CERT_DIR"/"$MAIN_URL".crt ]; then
    echo "Make certificates (using mkcert)"
    mkcert -install
    cd $CERT_DIR
    # shellcheck disable=SC2086
    mkcert -cert-file "$MAIN_URL".crt -key-file "$MAIN_URL".key *.$MAIN_URL
    openssl dhparam -out ssl-dhparams.pem 2048
  fi
}
