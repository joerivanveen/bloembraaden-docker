#!/usr/bin/env bash

configure() {
  cat /tmp/config/config.json | envsubst > /usr/share/nginx/config.json
  chmod 644 /usr/share/nginx/config.json
}
