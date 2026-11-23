#!/usr/bin/env sh
set -eu

# The cache may have been created on the host with a different dependency set.
rm -f bootstrap/cache/*.php

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

exec "$@"
