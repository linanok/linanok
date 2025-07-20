# Multi-stage Dockerfile for the web application
# This Dockerfile uses a multi-stage build process to optimize the final image size
# and separate build dependencies from runtime dependencies

# Stage 1: Build Dependencies and Assets
# This stage installs PHP dependencies, extensions, and builds frontend assets
FROM dunglas/frankenphp:php8.4-alpine AS build_stage
WORKDIR /app

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    postgresql-dev \
    oniguruma-dev \
    openssl-dev \
    libxml2-dev \
    curl-dev \
    icu-dev \
    libzip-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install \
    pcntl \
    pdo_pgsql \
    pgsql \
    opcache \
    intl \
    zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# Install Node.js v22 and npm
RUN apk add --no-cache --repository=https://dl-cdn.alpinelinux.org/alpine/edge/main \
    nodejs=~22 \
    npm

# Copy Composer binary from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy source files
COPY . .

# Install production dependencies only
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --optimize-autoloader

# Install frontend dependencies
RUN npm ci

# Build frontend assets
RUN npm run build

# Publish Filament assets
RUN php artisan filament:assets

# Stage 2: Production Runtime
# This stage creates the final production image
FROM dunglas/frankenphp:php8.4-alpine AS production
WORKDIR /app

# Install runtime dependencies and PHP extensions
RUN apk add --no-cache \
    curl \
    git \
    unzip \
    postgresql-dev \
    oniguruma-dev \
    openssl-dev \
    libxml2-dev \
    curl-dev \
    icu-dev \
    libzip-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install \
    pcntl \
    pdo_pgsql \
    pgsql \
    opcache \
    intl \
    zip \
    && pecl install redis \
    && docker-php-ext-enable redis

# Copy application files
COPY . .

# Copy built assets from build stage with correct permissions
COPY --from=build_stage --chown=appuser:appuser /app/vendor ./vendor
COPY --from=build_stage --chown=appuser:appuser /app/public ./public

# Set up entrypoint script
COPY --chown=appuser:appuser entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Use entrypoint script to handle container startup
ENTRYPOINT ["entrypoint.sh"]
