FROM php:8.3-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    git \
    curl \
    libicu-dev \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_pgsql opcache intl zip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html

RUN composer install --no-interaction --prefer-dist --no-progress --optimize-autoloader \
    && php bin/console cache:clear \
    && chown -R www-data:www-data var public

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/www.conf

EXPOSE 80

CMD sh -c "php-fpm -D && nginx -g 'daemon off;'"
