#!/bin/sh
set -eu

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/private \
    storage/app/public \
    bootstrap/cache

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

if [ -f .env ] && grep -q '^APP_KEY=$' .env; then
    export APP_KEY="$(php artisan key:generate --show --no-interaction)"
    php artisan key:generate --force --no-interaction >/dev/null
fi

php artisan storage:link --force --no-interaction >/dev/null 2>&1 || true

if [ -f storage/installed.lock ]; then
    php artisan migrate --force --no-interaction
fi

exec "$@"
