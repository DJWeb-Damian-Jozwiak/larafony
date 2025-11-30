# Build stage for PHP 8.5
FROM php:8.5-cli-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    curl \
    bash \
    libzip-dev \
    mysql-client \
    sqlite \
    libmemcached-dev \
    zlib-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Install Redis and Memcached extensions from source (PECL may not have PHP 8.5 versions)
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    autoconf \
    g++ \
    make \
    # Install phpredis
    && cd /tmp \
    && git clone https://github.com/phpredis/phpredis.git \
    && cd phpredis \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && docker-php-ext-enable redis \
    # Install php-memcached
    && cd /tmp \
    && git clone https://github.com/php-memcached-dev/php-memcached.git \
    && cd php-memcached \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && docker-php-ext-enable memcached \
    # Cleanup
    && cd / \
    && rm -rf /tmp/phpredis /tmp/php-memcached \
    && apk del .build-deps

# Enable PHP extensions required by framework
RUN docker-php-ext-enable pdo pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Install dependencies (without dev for production-like layer)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Development stage
FROM base AS dev

WORKDIR /app

# Install dev dependencies
RUN composer install --prefer-dist --no-interaction

# Copy application code
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize

# Create necessary directories
RUN mkdir -p .phpunit.cache coverage tests/storage

# Create a non-root user for running tests
# This is important for permission-based tests to work correctly
RUN addgroup -g 1000 appuser && \
    adduser -D -u 1000 -G appuser appuser

# Set permissions
RUN chown -R appuser:appuser /app && \
    chmod -R 755 /app && \
    chmod -R 777 .phpunit.cache coverage tests/storage || true

# Switch to non-root user
USER appuser

# Default command runs tests
CMD ["php", "vendor/bin/phpunit", "--no-coverage"]

# Testing stage with coverage using PCOV
FROM base AS test-base

# Install build dependencies and build PCOV from source
# PCOV is preferred over Xdebug because:
# - 2-3x faster for code coverage collection
# - No debugging overhead
# - Built from source for PHP 8.5 compatibility (PECL version may not be available)
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    autoconf \
    g++ \
    make \
    # Clone PCOV from official repository
    && cd /tmp \
    && git clone https://github.com/krakjoe/pcov.git \
    && cd pcov \
    # Build PCOV extension
    && phpize \
    && ./configure --enable-pcov \
    && make \
    && make install \
    # Enable the extension
    && docker-php-ext-enable pcov \
    # Clean up build files and dependencies to reduce image size
    && cd / \
    && rm -rf /tmp/pcov \
    && apk del .build-deps

# Configure PCOV for optimal performance
# - Only scan src/ directory (not vendor/)
# - Enable coverage collection by default
RUN echo "pcov.enabled=1" >> /usr/local/etc/php/conf.d/docker-php-ext-pcov.ini \
    && echo "pcov.directory=/app/src" >> /usr/local/etc/php/conf.d/docker-php-ext-pcov.ini \
    && echo "pcov.exclude=\"~vendor~\"" >> /usr/local/etc/php/conf.d/docker-php-ext-pcov.ini

# Now build the test stage with application code
FROM test-base AS test

WORKDIR /app

# Install dev dependencies
RUN composer install --prefer-dist --no-interaction

# Copy application code
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize

# Create necessary directories
RUN mkdir -p .phpunit.cache coverage tests/storage

# Create a non-root user for running tests
# This is important for permission-based tests to work correctly
RUN addgroup -g 1000 appuser && \
    adduser -D -u 1000 -G appuser appuser

# Set permissions
RUN chown -R appuser:appuser /app && \
    chmod -R 755 /app && \
    chmod -R 777 .phpunit.cache coverage tests/storage || true

# Switch to non-root user
USER appuser

CMD ["php", "vendor/bin/phpunit"]

# Quality stage for running all quality checks
FROM test AS quality

CMD ["composer", "quality"]
