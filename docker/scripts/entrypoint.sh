#!/usr/bin/env bash

for script in /tmp/scripts/_*; do source $script; done

# Start php fpm in the background in advance.
start-php-fpm

#make-certificates # TODO only make certificates when they are absent or outdated

install-bloembraaden

echo "Ready"
sleep infinity
