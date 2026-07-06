FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --optimize

FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

RUN docker-php-ext-install pdo_mysql

COPY --from=vendor /app /var/www/html

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
