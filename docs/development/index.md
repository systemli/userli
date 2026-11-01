# Getting started

Userli is a Symfony-based web application for self-managing email users with mailbox encryption support (Dovecot).
It is built with PHP, Symfony, Doctrine ORM, and uses TailwindCSS with Symfony UX on the frontend.

We provide a `docker-compose.yml` that starts Userli with MariaDB, Dovecot, Roundcube, Mailcatcher, Redis, and a webhook tester to set up a complete development environment.

!!! warning

    This setup is meant for local development only. Do not use it in
    production. See the [Operations Guide](../operations/index.md) for
    production installations.

| Service      | URL                                                          | Purpose                     |
| ------------ | ------------------------------------------------------------ | --------------------------- |
| Userli       | [http://localhost:8000](http://localhost:8000)                | Main application            |
| Roundcube    | [http://localhost:8001](http://localhost:8001)                | Webmail client              |
| Mailcatcher  | [http://localhost:1080](http://localhost:1080)                | Catches all outgoing emails |
| Webhook test | [http://localhost:9000](http://localhost:9000)                | Webhook endpoint testing    |

## Requirements

- `podman` (or `docker`) with compose support
- `yarn`
- `composer`
- `make`

!!! info
    If you don't have Podman or Docker installed, see the [Podman](https://podman.io/getting-started/installation) or [Docker](https://docs.docker.com/get-docker/) installation instructions.

## Setup

For first-time setup, run:

```shell
make dev
```

This will:

1. Install PHP dependencies and build frontend assets (`make assets`)
2. Start all containers (MariaDB, Redis, Caddy, Userli, worker, scheduler, Mailcatcher, webhook tester)
3. Wait for MariaDB to be ready
4. Run database migrations

The Makefile auto-detects whether you have Podman or Docker installed.

### Load fixtures

To populate the environment with example data, run

```shell
make fixtures 
```

This

1. loads sample data (users, domains, API tokens, ...) and
2. starts Dovecot and Roundcube (`mail` profile).

When it finishes, open [http://localhost:8000](http://localhost:8000) and log in with `admin@example.org` / `password`.


!!! info
    The fixtures create user accounts (`admin`, `user`, `support` and `suspicious`, among others) on the domain `example.org`, all with the password `password`.
    They also create sample aliases and vouchers.
    See `src/DataFixtures` for details.

!!! info
    Dovecot and Roundcube are in the `mail` profile and must be started after the fixtures are loaded in the database, otherwise Dovecot will fail because the API token it is configured to use will not exists in the database. `make fixtures` handles both in the right order. 

!!! tip
    Run `make` without arguments to see all available targets.

## Next steps

* Get to know the code: [Architecture](architecture.md)
* Read the [contribution guidelines](contributing.md)
* Run the [tests](testing.md)
* Watch the frontend while you work:

    ```shell
    yarn watch
    ```

## Background services

The development environment runs three containers for the Userli application:

| Container          | Purpose                                           |
| ------------------ | ------------------------------------------------- |
| `userli`           | PHP-FPM, served by the `caddy` container          |
| `userli-worker`    | Async message consumer (`messenger:consume async`) |
| `userli-scheduler` | Scheduled tasks (`messenger:consume scheduler_maintenance`) |

The worker processes async messages (e.g. email sending, webhook delivery).
The scheduler runs recurring maintenance tasks defined in `src/Schedule/`.

## Logs

Userli uses [Monolog](https://symfony.com/doc/7.4/logging.html) for logging, configured in `config/packages/monolog.yaml`.
Logs are JSON-formatted.

In development, logs are written to `var/log/dev.log`:

```shell
tail -f var/log/dev.log | jq
```

To inspect container logs:

=== "podman"

    ```shell
    podman compose logs -f userli
    ```

=== "docker"

    ```shell
    docker compose logs -f userli
    ```

## Roundcube plugin

Roundcube uses the released [Roundcube Userli plugin](https://github.com/systemli/roundcube-userli-plugin).
To work on a local checkout instead, mount it in a `docker-compose.override.yml`:

```yaml
---
services:
  roundcube:
    environment:
      ROUNDCUBEMAIL_COMPOSER_PLUGINS: ""
    volumes:
      - ../roundcube-userli-plugin:/var/www/html/plugins/userli:ro
```

To sign in to Roundcube with a security key, set **Additional Security Key
Origins** to `http://localhost:8001` in the admin settings. Both ports share
the security key domain `localhost`.

## Troubleshooting

On systems with SELinux enabled, the webserver might throw an error due to broken filesystem permissions.
Create a `docker-compose.override.yml` in the root directory:

```yaml
---
services:
  userli:
    security_opt:
      - label=disable

  dovecot:
    security_opt:
      - label=disable

  roundcube:
    security_opt:
      - label=disable
```
