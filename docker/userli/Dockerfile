# Stage 1: JS build (independent, no PHP needed)
FROM docker.io/node:22-alpine AS node-builder
WORKDIR /app
COPY package.json yarn.lock webpack.config.js postcss.config.mjs tsconfig.json ./
COPY assets/ assets/
RUN yarn install --frozen-lockfile && yarn encore production

# Stage 2: PHP build
FROM docker.io/composer:2.8 AS composer

FROM docker.io/php:8.4-cli-alpine AS php-builder
RUN apk add --no-cache icu-dev libzip-dev zip
RUN docker-php-ext-configure intl && \
    docker-php-ext-install -j$(nproc) intl zip
COPY . /var/www/html/
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=node-builder /app/public/build /var/www/html/public/build/
WORKDIR /var/www/html
RUN composer install --no-scripts && \
    bin/console assets:install

# Stage 3: Final runtime
FROM docker.io/php:8.4-fpm-alpine

# Install build dependencies (virtual package for easy removal)
RUN apk add --no-cache --virtual .build-deps \
        autoconf g++ make linux-headers \
        libpng-dev libsodium-dev libzip-dev zlib-dev icu-dev && \
    # Install runtime dependencies (these stay)
    apk add --no-cache \
        libpng libsodium libzip zlib icu-libs gnupg zip && \
    docker-php-ext-configure intl && \
    docker-php-ext-install -j$(nproc) gd opcache pdo_mysql sodium zip intl && \
    pecl install redis xdebug && \
    docker-php-ext-enable redis xdebug && \
    apk del .build-deps

COPY --from=php-builder /var/www/html /var/www/html
WORKDIR /var/www/html
