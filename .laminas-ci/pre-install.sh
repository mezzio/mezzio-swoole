#!/bin/bash

JOB=$3
PHP_VERSION=$(echo "${JOB}" | jq -r '.php')

apt update

# Build package list
PACKAGES="php${PHP_VERSION}-swoole"

# Install all packages at once
apt install -y $PACKAGES
