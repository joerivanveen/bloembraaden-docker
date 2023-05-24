#!/usr/bin/env bash

make-certificates() {
  echo "Make certificates (using mkcert)"
  mkcert -install
  cd $CERT_DIR
  mkcert -cert-file $MAIN_URL.crt -key-file $MAIN_URL.key *.$MAIN_URL
  openssl dhparam -out ssl-dhparams.pem 2048
}
