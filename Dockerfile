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
    && mkdir -p config/jwt \
    && if [ ! -f config/jwt/private.pem ] || [ ! -f config/jwt/public.pem ]; then \
        openssl genrsa -out config/jwt/private.pem 4096 && \
        openssl rsa -in config/jwt/private.pem -pubout -out config/jwt/public.pem; \
    fi \
    && chmod 600 config/jwt/private.pem \
    && chmod 644 config/jwt/public.pem \
    && php bin/console cache:clear \
    && chown -R www-data:www-data var public config/jwt

RUN printf '%s\n' \
    'log_errors = On' \
    'error_log = /proc/self/fd/2' \
    > /usr/local/etc/php/conf.d/99-error-log.ini \
    && printf '%s\n' \
    'catch_workers_output = yes' \
    'decorate_workers_output = no' \
    'php_admin_flag[log_errors] = on' \
    'php_admin_value[error_log] = /proc/self/fd/2' \
    >> /usr/local/etc/php-fpm.d/www.conf \
    && ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

RUN rm -f /etc/nginx/conf.d/default.conf /etc/nginx/sites-enabled/default
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/www.conf

EXPOSE 80

CMD sh -c "php-fpm -D && nginx -g 'daemon off;'"
