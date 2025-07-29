# Multi-stage Dockerfile for the Queue Worker service
# This container runs Laravel's queue worker to process background jobs
# It's optimized for running queue processing tasks in a separate container

# Stage 1: Build Dependencies
# This stage handles dependency installation and build tools
FROM php:8.4-cli-alpine AS build_dependencies

# Install system dependencies required for PHP extensions and build tools
RUN apk add --no-cache \
    linux-headers \
    $PHPIZE_DEPS \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git

# Install required PHP extensions for the application
RUN docker-php-ext-install pdo pdo_pgsql zip pcntl intl

# Install Redis extension for queue processing
RUN pecl install redis && docker-php-ext-enable redis

# Get latest Composer for dependency management
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory for the application
WORKDIR /app

# Copy application files
COPY . .

# Install production dependencies only
RUN composer install --no-dev --optimize-autoloader

# Stage 2: Production Runtime
# This stage creates the final production image with only runtime dependencies
FROM php:8.4-cli-alpine AS production

# Install only runtime dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    $PHPIZE_DEPS

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql zip pcntl intl \
    && pecl install redis \
    && docker-php-ext-enable redis

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Copy installed dependencies from build stage
COPY --from=build_dependencies --chown=www-data:www-data /app/vendor ./vendor

# Copy custom PHP configuration
COPY deployment/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Set proper permissions for Laravel storage and cache directories
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Switch to non-root user for security
USER www-data

# Start the queue worker with optimized settings
# --tries=3: Retry failed jobs up to 3 times
# --max-jobs=1000: Restart the worker after processing 1000 jobs to prevent memory leaks
CMD ["php", "artisan", "queue:work", "--tries=3", "--max-jobs=1000"]
