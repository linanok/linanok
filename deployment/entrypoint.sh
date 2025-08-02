#!/bin/sh
set -e

cd /app

# Run database migrations
php artisan migrate --force --seed --seeder=ProductionDatabaseSeeder

# Run Laravel optimization commands
php artisan optimize:clear
php artisan filament:optimize-clear
php artisan optimize
php artisan filament:optimize

# Start FrankenPHP server
exec php artisan octane:start --server=frankenphp --workers=$OCTANE_WORKERS
