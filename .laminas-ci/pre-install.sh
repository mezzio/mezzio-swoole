#!/bin/bash

PHP_VERSION=$4

if [ -z "${PHP_VERSION}" ]; then
    echo "Missing PHP version argument"
    exit 1
fi

set -e -o pipefail
apt update

# Build package list
PACKAGE="php${PHP_VERSION}-swoole"

# Install all packages at once
apt install -y "${PACKAGE}"
