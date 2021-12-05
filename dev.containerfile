FROM composer AS composer
COPY composer.* /app
RUN composer install --ignore-platform-reqs

FROM php:8.1.0
RUN docker-php-ext-install pcntl && pecl install pcov && docker-php-ext-enable pcov
COPY --from=composer /app/vendor /app/vendor
COPY src /app/src
COPY tests /app/tests
WORKDIR /app
