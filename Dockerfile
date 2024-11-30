FROM composer AS composer

FROM php:8.2-apache-bookworm
RUN apt-get update && apt-get install -y libpng-dev libzip-dev nodejs npm zlib1g-dev zip
RUN docker-php-ext-install -j$(nproc) gd zip
COPY . /var/www/html
COPY userli.conf /etc/apache2/sites-enabled/000-default.conf
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN mv .env.test .env
RUN APP_ENV=test composer install --no-scripts &&   bin/console doctrine:schema:create --env=test &&   bin/console doctrine:fixtures:load --group=basic --env=test -n
RUN npm install --global yarn && yarn install && yarn encore production
RUN bin/console assets:install
RUN chown -R www-data:www-data var
