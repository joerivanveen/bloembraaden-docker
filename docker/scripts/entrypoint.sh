#!/usr/bin/env bash

for script in /tmp/scripts/_*; do source $script; done

# Start php fpm in the background in advance.
start-php-fpm

make-certificates

install-bloembraaden

#crond -f
service cron start

echo "Ready"
sleep infinity
