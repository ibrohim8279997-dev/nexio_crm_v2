# Nexio CRM API (Laravel, PHP-FPM). Build context: ./backend
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix

FROM php:8.3-fpm-alpine
RUN apk add --no-cache postgresql-client libpq-dev libzip-dev icu-dev oniguruma-dev nginx supervisor \
 && docker-php-ext-install pdo_pgsql pgsql zip intl mbstring opcache pcntl \
 && apk add --no-cache --virtual .build $PHPIZE_DEPS && pecl install redis && docker-php-ext-enable redis && apk del .build
WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --optimize --no-dev --no-scripts \
 && mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/backups bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD wget -qO- http://127.0.0.1/api/health || exit 1
ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
