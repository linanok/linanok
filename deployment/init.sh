#!/bin/sh
set -e

cd /app

# Run Laravel optimization commands
php artisan optimize:clear
php artisan optimize
