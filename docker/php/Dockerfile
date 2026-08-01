FROM php:8.4-fpm-alpine

# Runtime libraries the PHP extensions link against, plus the build toolchain
# ($PHPIZE_DEPS) which is dropped again in the same layer to keep the image small.
RUN apk add --no-cache \
        bash \
        freetype-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# The bind-mounted source is owned by the host user; www-data still needs to be
# able to write storage/ and bootstrap/cache/.
USER www-data

EXPOSE 9000

CMD ["php-fpm"]
