# Multi-stage Dockerfile for the CLI service
# This container runs Laravel CLI commands including queue workers, schedulers, and other artisan commands
# It's optimized for running background tasks and command-line operations

# Stage 1: Build Dependencies
# This stage handles dependency installation and build tools
FROM php:8.4-cli AS build_dependencies

# Install system dependencies required for PHP extensions and build tools
RUN apt-get update && apt-get install -y \
    build-essential \
    linux-libc-dev \
    libsqlite3-dev \
    $PHPIZE_DEPS \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    git \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions for the application
RUN docker-php-ext-install pdo pdo_pgsql pgsql pdo_mysql mysqli pdo_sqlite zip pcntl intl

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
FROM php:8.4-cli AS production

# Install only runtime dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libsqlite3-dev \
    $PHPIZE_DEPS \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pgsql pdo_mysql mysqli pdo_sqlite zip pcntl intl \
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

# Build arguments for dynamic metadata
ARG VERSION=latest
ARG BUILD_DATE
ARG VCS_REF
ARG VCS_URL=https://github.com/linanok/linanok

# Manifest/Metadata Labels
LABEL org.opencontainers.image.title="Linanok CLI"
LABEL org.opencontainers.image.description="Laravel CLI container for Linanok platform - handles artisan commands, queue workers, and scheduled tasks"
LABEL org.opencontainers.image.version="${VERSION}"
LABEL org.opencontainers.image.created="${BUILD_DATE}"
LABEL org.opencontainers.image.source="${VCS_URL}"
LABEL org.opencontainers.image.revision="${VCS_REF}"
LABEL org.opencontainers.image.licenses="MIT"
LABEL org.opencontainers.image.vendor="Linanok"
LABEL org.opencontainers.image.authors="Linanok Team"
LABEL org.opencontainers.image.documentation="${VCS_URL}/blob/main/README.md"
LABEL app.name="linanok"
LABEL app.component="cli"
LABEL app.version="${VERSION}"
LABEL app.framework="laravel"
LABEL app.php.version="8.4"
LABEL app.build.date="${BUILD_DATE}"
LABEL app.build.revision="${VCS_REF}"

# The command will be specified in docker-compose.yml
# This allows for more flexible container usage (queue worker, scheduler, etc.)
