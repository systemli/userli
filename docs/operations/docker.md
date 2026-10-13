# Run with containers

A container image is published on Docker Hub as
[`systemli/userli`](https://hub.docker.com/r/systemli/userli).

!!! danger "Not recommended for production"

    We use the container image and the Compose setup for
    [local development](../development/index.md) only. They are not intended
    for production, are not maintained to production standards and have not
    been hardened for it. For production, install Userli
    [from a release tarball](installation.md).

!!! warning "Only `latest`"

    The image is built from the `main` branch on every push and only tagged
    `latest` (for `linux/amd64` and `linux/arm64`). There are no version tags
    yet. If you use it anyway, pin the image by digest and test updates before
    deploying them.

## The image

* Based on `php:8.4-fpm-alpine`, it runs **PHP-FPM on port 9000** (FastCGI).
  You need a webserver in front of it, for example Caddy.
* The application lives in `/var/www/html`, the document root is
  `/var/www/html/public`.
* It includes the PHP extensions `gd`, `intl`, `opcache`, `pdo_mysql`,
  `redis`, `sodium` and `zip`, plus GnuPG.
* It does **not** run database migrations on start. Run them after each update.
* Configuration is done with [environment variables](configuration.md).

The same image is used for three containers: the web application, the
[worker and the scheduler](background-services.md).

## Example with Compose

This example runs Userli with Caddy, MariaDB and Redis. Adjust passwords,
domains and TLS to your needs.

```yaml title="compose.yml"
x-userli: &userli
  image: docker.io/systemli/userli:latest
  restart: unless-stopped
  environment:
    APP_ENV: prod
    APP_SECRET: change-me
    DATABASE_URL: mysql://userli:change-me@mariadb:3306/userli?serverVersion=mariadb-10.11.0
    MAILER_DSN: smtp://mail.example.org:25
    DEFAULT_URI: https://users.example.org
    REDIS_URL: redis://redis:6379
  depends_on:
    - mariadb
    - redis

services:
  userli:
    <<: *userli
    volumes:
      - public:/var/www/html/public

  userli-worker:
    <<: *userli
    command: php bin/console messenger:consume async --time-limit=3600

  userli-scheduler:
    <<: *userli
    command: php bin/console messenger:consume scheduler_maintenance --time-limit=3600

  caddy:
    image: docker.io/caddy:2-alpine
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./Caddyfile:/etc/caddy/Caddyfile:ro
      - public:/var/www/html/public:ro
      - caddy_data:/data

  mariadb:
    image: docker.io/mariadb:10.11
    restart: unless-stopped
    environment:
      MARIADB_DATABASE: userli
      MARIADB_USER: userli
      MARIADB_PASSWORD: change-me
      MARIADB_RANDOM_ROOT_PASSWORD: "yes"
    volumes:
      - mariadb:/var/lib/mysql

  redis:
    image: docker.io/redis:7-alpine
    restart: unless-stopped

volumes:
  public:
  mariadb:
  caddy_data:
```

```text title="Caddyfile"
users.example.org {
    root * /var/www/html/public
    php_fastcgi userli:9000
    file_server
}
```

The `public` volume shares the static files (CSS, JavaScript, images) of the
image with Caddy.

!!! note "Static files after updates"

    A named volume is only filled from the image when it is created. After
    updating the image, remove the `public` volume (or copy the files) so that
    Caddy serves the new assets.

Start the containers and create the database schema:

```shell
docker compose up -d
docker compose exec userli bin/console doctrine:migrations:migrate --no-interaction
```

Continue with the [initial setup](initial-setup.md).

## Updating

```shell
docker compose pull
docker compose up -d
docker compose exec userli bin/console doctrine:migrations:migrate --no-interaction
```

Read [Updating](updating.md) before each update.

## Logs

In production, Userli logs errors to **syslog** with the ident `userli`. A
container usually has no syslog daemon, so only deprecation messages reach
stderr. Use the worker output and the webserver logs, or mount a syslog socket
into the container. See [Monitoring and logs](monitoring.md).
