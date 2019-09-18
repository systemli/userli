FROM php:7.3-cli

RUN apt-get update && \
    apt-get install -y libsodium-dev libzip-dev git unzip && \
    pecl install libsodium && \
    pecl install zip && \
    curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer && \
    chmod +x /usr/local/bin/composer
