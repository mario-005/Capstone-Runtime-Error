FROM php:8.4-cli-alpine

RUN apk add --no-cache \
        git \
        icu-dev \
        libpq-dev \
        oniguruma-dev \
        unzip \
    && docker-php-ext-install intl mbstring pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
