FROM php:8.4-fpm-alpine

# -----------------------------------------------------------------------------
# System dependencies
# -----------------------------------------------------------------------------
RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    postgresql-dev \
    oniguruma-dev \
    autoconf \
    g++ \
    make \
    linux-headers

# -----------------------------------------------------------------------------
# PHP extensions required by Laravel 12 + PostgreSQL + Redis
# -----------------------------------------------------------------------------
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
        pcntl

# Redis extension via PECL
RUN pecl install redis \
    && docker-php-ext-enable redis

# -----------------------------------------------------------------------------
# Composer
# -----------------------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# -----------------------------------------------------------------------------
# Non-root user matching common host UID for writable volumes
# -----------------------------------------------------------------------------
RUN addgroup -g 1000 www && adduser -u 1000 -G www -s /bin/bash -D www

WORKDIR /var/www/html

USER www

EXPOSE 9000

CMD ["php-fpm"]
