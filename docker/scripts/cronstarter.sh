#!/usr/bin/env bash

source /tmp/scripts/_configure.sh

configure

crond -f

echo "Ready"
sleep infinity
