FROM php:8.4-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache \
    build-base \
    autoconf \
    $PHPIZE_DEPS \
    linux-headers \
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

# Install Xdebug for code coverage (and debugging if needed)
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && { \
        echo "xdebug.mode=${XDEBUG_MODE:-off}"; \
        echo "xdebug.start_with_request=no"; \
      } > /usr/local/etc/php/conf.d/99-xdebug.ini

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
