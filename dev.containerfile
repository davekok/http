FROM composer AS composer
COPY composer.* /app
RUN composer install --ignore-platform-reqs --dev

FROM php:8.1.0RC4
RUN pecl install pcov && docker-php-ext-enable pcov
COPY --from=composer /app/vendor /app/vendor
COPY src /app/src
COPY tests /app/tests
