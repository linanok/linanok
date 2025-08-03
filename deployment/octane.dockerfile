# Multi-stage Dockerfile for the web application
# This Dockerfile uses a multi-stage build process to optimize the final image size
# and separate build dependencies from runtime dependencies

# Stage 1: Build Dependencies and Assets
# This stage installs PHP dependencies, extensions, and builds frontend assets
FROM dunglas/frankenphp:php8.4 AS build_stage
WORKDIR /app

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    build-essential \
    curl \
    git \
    unzip \
    libpq-dev \
    libonig-dev \
    libssl-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libicu-dev \
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
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js v22 and npm
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs

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
FROM dunglas/frankenphp:php8.4 AS production
WORKDIR /app

# Install runtime dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    build-essential \
    curl \
    git \
    unzip \
    libpq-dev \
    libonig-dev \
    libssl-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libicu-dev \
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
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy application files
COPY . .

# Copy built assets from build stage with correct permissions
COPY --from=build_stage --chown=appuser:appuser /app/vendor ./vendor
COPY --from=build_stage --chown=appuser:appuser /app/public ./public

# Set up PHP production configuration
RUN cp $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini

# Copy custom PHP configuration
COPY deployment/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Set up entrypoint script
COPY --chown=appuser:appuser deployment/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Create storage directories and set proper permissions
RUN mkdir -p /app/storage/logs /app/storage/framework/cache /app/storage/framework/sessions /app/storage/framework/views /app/bootstrap/cache
RUN chmod -R 777 /app/storage /app/bootstrap/cache

# Build arguments for dynamic metadata
ARG VERSION=latest
ARG BUILD_DATE
ARG VCS_REF
ARG VCS_URL=https://github.com/linanok/linanok

# Manifest/Metadata Labels
LABEL org.opencontainers.image.title="Linanok Web Application"
LABEL org.opencontainers.image.description="Laravel web application container for Linanok platform - main web server with FrankenPHP and Octane"
LABEL org.opencontainers.image.version="${VERSION}"
LABEL org.opencontainers.image.created="${BUILD_DATE}"
LABEL org.opencontainers.image.source="${VCS_URL}"
LABEL org.opencontainers.image.revision="${VCS_REF}"
LABEL org.opencontainers.image.licenses="MIT"
LABEL org.opencontainers.image.vendor="Linanok"
LABEL org.opencontainers.image.authors="Linanok Team"
LABEL org.opencontainers.image.documentation="${VCS_URL}/blob/main/README.md"
LABEL app.name="linanok"
LABEL app.component="web-server"
LABEL app.version="${VERSION}"
LABEL app.framework="laravel"
LABEL app.php.version="8.4"
LABEL app.server="frankenphp"
LABEL app.octane="true"
LABEL app.build.date="${BUILD_DATE}"
LABEL app.build.revision="${VCS_REF}"

# Use entrypoint script to handle container startup
ENTRYPOINT ["entrypoint.sh"]
