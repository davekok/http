FROM php:8.1.6
RUN docker-php-ext-install pcntl && pecl install pcov && docker-php-ext-enable pcov
