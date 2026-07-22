#!/bin/sh
set -eu

php artisan config:clear
php artisan config:cache
php artisan migrate --force
php artisan app:ensure-admin
php artisan view:cache

exec frankenphp run --config /app/docker/Caddyfile
