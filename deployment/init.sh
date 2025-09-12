#!/bin/sh
set -e

cd /app

# Ensure storage directories exist with proper permissions (in case init didn't run)
mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views storage/app storage/octane bootstrap/cache
chmod -R 0755 storage bootstrap/cache

# Run Laravel optimization commands
php artisan optimize:clear
php artisan optimize

# Download MaxMind GeoLite2-Country database if license key is configured
if [ -n "$MAXMIND_LICENSE_KEY" ]; then
    echo "Downloading MaxMind GeoLite2-Country database..."
    php artisan maxmind:download --quiet || echo "Warning: Failed to download MaxMind database. Please check your license key."
else
    echo "Warning: MAXMIND_LICENSE_KEY not set. Skipping MaxMind database download."
fi
