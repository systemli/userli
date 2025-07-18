FROM docker.io/composer:2.8.3 AS composer

FROM docker.io/php:8.2-cli AS builder

RUN apt-get update && \
    apt-get install -y libzip-dev nodejs npm zip
RUN docker-php-ext-install -j$(nproc) zip

COPY . /var/www/html/
COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN composer install --no-scripts && \
    npm install --global yarn && \
    yarn install && \
    yarn encore production && \
    bin/console assets:install

FROM docker.io/php:8.2-apache-bookworm

RUN apt-get update && \
    apt-get install -y libpng-dev libsodium-dev libsqlite3-dev libzip-dev zlib1g-dev zip gpg libicu-dev && \
    a2enmod rewrite && \
    docker-php-ext-configure intl && \
    docker-php-ext-install -j$(nproc) gd opcache pdo_mysql pdo_sqlite sodium zip intl && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug

COPY --from=builder /var/www/html /var/www/html
COPY docker/userli/apache.conf /etc/apache2/sites-available/000-default.conf
