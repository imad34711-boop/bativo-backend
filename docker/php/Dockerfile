FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    unzip \
    libpq \
    postgresql-dev \
    nginx \
    && docker-php-ext-install pdo pdo_pgsql opcache \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

COPY . /var/www

RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts \
    && php bin/console cache:clear \
    && chown -R www-data:www-data /var/www/var /var/www/public

CMD ["php-fpm"]
