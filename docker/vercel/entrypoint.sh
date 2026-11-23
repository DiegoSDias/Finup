#!/bin/sh
set -eu

# Only /tmp is writable in the Vercel function. Financial data stays in MySQL.
export LARAVEL_STORAGE_PATH=/tmp/storage
export VIEW_COMPILED_PATH=/tmp/storage/framework/views
export APP_CONFIG_CACHE=/tmp/bootstrap/config.php
export APP_EVENTS_CACHE=/tmp/bootstrap/events.php
export APP_PACKAGES_CACHE=/tmp/bootstrap/packages.php
export APP_ROUTES_CACHE=/tmp/bootstrap/routes.php
export APP_SERVICES_CACHE=/tmp/bootstrap/services.php

mkdir -p \
    /tmp/bootstrap \
    /tmp/storage/app/private \
    /tmp/storage/app/public \
    /tmp/storage/framework/cache/data \
    /tmp/storage/framework/sessions \
    /tmp/storage/framework/views \
    /tmp/storage/logs

exec "$@"
