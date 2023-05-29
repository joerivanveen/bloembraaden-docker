#!/usr/bin/env bash

for script in /tmp/scripts/_*; do source $script; done

configure

# Start php fpm in the background in advance.
start-php-fpm

make-certificates

install-bloembraaden

echo "Ready"
sleep infinity
