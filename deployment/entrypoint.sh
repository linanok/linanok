#!/bin/sh
set -e

cd /app

# Run database migrations
php artisan migrate --force --seed --seeder=ProductionDatabaseSeeder

# Start FrankenPHP server
exec php artisan octane:start --server=frankenphp --workers=$OCTANE_WORKERS
