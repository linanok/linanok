#!/bin/sh
set -e

cd /app

# Ensure storage directories exist with proper permissions (in case init didn't run)
mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views storage/app storage/octane bootstrap/cache
chmod -R 0755 storage bootstrap/cache

# Run database migrations
php artisan migrate --force --seed --seeder=ProductionDatabaseSeeder

# Start FrankenPHP server
exec php artisan octane:start --server=frankenphp --workers=$OCTANE_WORKERS
