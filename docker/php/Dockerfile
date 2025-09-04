FROM php:8.1-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache \
    build-base \
    autoconf \
    libzip-dev \
    postgresql-dev \
    icu-dev \
    git \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-configure intl \
    && docker-php-ext-install \
    pdo_pgsql \
    intl \
    zip \
    opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader

# Copy application files
COPY . .

# Run composer scripts
RUN composer dump-autoload --optimize
RUN composer run-script post-install-cmd

# Set permissions for storage and cache
RUN chown -R www-data:www-data /var/www/html/var \
    && chmod -R 775 /var/www/html/var

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
