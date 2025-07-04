# AdminKit Docker Image
# Multi-stage build for production-ready PHP application

# =====================================================
# Base PHP Image with Extensions
# =====================================================
FROM php:8.1-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    mysql-client \
    postgresql-client \
    redis \
    supervisor

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# =====================================================
# Development Stage
# =====================================================
FROM base AS development

# Install Xdebug for development
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Configure Xdebug
RUN echo "xdebug.mode=develop,debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Copy PHP configuration
COPY docker/php/php-dev.ini /usr/local/etc/php/conf.d/99-custom.ini

# Copy application code
COPY . .

# Install dependencies
RUN composer install --no-scripts --no-autoloader

# Generate autoloader
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/public \
    && chmod -R 777 /var/www/html/cache \
    && chmod -R 777 /var/www/html/logs

# Expose port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]

# =====================================================
# Production Stage
# =====================================================
FROM base AS production

# Install production dependencies only
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --optimize-autoloader

# Copy application code
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --classmap-authoritative

# Copy PHP configuration
COPY docker/php/php-prod.ini /usr/local/etc/php/conf.d/99-custom.ini

# Create cache and log directories
RUN mkdir -p cache logs public/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/public \
    && chmod -R 777 /var/www/html/cache \
    && chmod -R 777 /var/www/html/logs \
    && chmod -R 777 /var/www/html/public/uploads

# Remove development files
RUN rm -rf \
    .git \
    .gitignore \
    .env.example \
    docker \
    docs \
    tests \
    README.md \
    CONTRIBUTING.md \
    CHANGELOG.md

# Configure OPcache for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/99-custom.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/99-custom.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/99-custom.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/99-custom.ini

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD php vendor/bin/adminkit version || exit 1

# Expose port
EXPOSE 9000

# Use supervisor to manage processes
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# =====================================================
# Nginx Stage (Web Server)
# =====================================================
FROM nginx:alpine AS nginx

# Copy nginx configuration
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy static assets from application
COPY --from=production /var/www/html/public /var/www/html/public

# Set permissions
RUN chown -R nginx:nginx /var/www/html/public

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Start nginx
CMD ["nginx", "-g", "daemon off;"]
