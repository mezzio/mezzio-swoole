#!/bin/bash

JOB=$3
PHP_VERSION=$(echo "${JOB}" | jq -r '.php')

apt update
apt install -y "php${PHP_VERSION}-swoole"
