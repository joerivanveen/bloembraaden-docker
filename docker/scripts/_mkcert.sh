#!/usr/bin/env bash

make-certificates() {
  echo "Make certificates (using mkcert)"
  mkcert -install
  cd $CERT_DIR
  mkcert $MAIN_URL
  mkcert www.$MAIN_URL
  mkcert static.$MAIN_URL
}
