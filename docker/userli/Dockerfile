FROM docker.io/composer:2.8.12 AS composer

FROM docker.io/php:8.4-cli AS builder

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

FROM docker.io/php:8.4-apache-trixie

RUN apt-get update && \
    apt-get install -y libpng-dev libsodium-dev libzip-dev zlib1g-dev zip gpg libicu-dev && \
    a2enmod rewrite && \
    docker-php-ext-configure intl && \
    docker-php-ext-install -j$(nproc) gd opcache pdo_mysql sodium zip intl && \
    pecl install redis xdebug && \
    docker-php-ext-enable redis xdebug

COPY --from=builder /var/www/html /var/www/html
COPY docker/userli/apache.conf /etc/apache2/sites-available/000-default.conf
