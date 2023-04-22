#!/usr/bin/env bash

start-php-fpm() {
  echo "Starting php-fpm..."

  if pidof -s php-fpm > /dev/null; then
    echo "php-fpm is already running"
    return
  fi

  php-fpm -D

  if [ "$?" -eq 0 ]; then
    echo "Started php-fpm."
  else
    echo "Failed to start php-fpm."
    exit 1
  fi
}
