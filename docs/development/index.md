# Getting started

Userli is a Symfony-based web application for self-managing email users with mailbox encryption support (Dovecot).
It is built with PHP, Symfony, Doctrine ORM, and uses TailwindCSS with Symfony UX on the frontend.

We provide a `docker-compose.yml` that starts Userli with MariaDB, Dovecot, Roundcube, Mailcatcher, Redis, and a webhook tester to set up a complete development environment.

!!! warning

    This setup is meant for local development only. Do not use it in
    production. See the [Operations Guide](../operations/index.md) for
    production installations.

| Service      | URL                                                                  | Purpose                     |
| ------------ | -------------------------------------------------------------------- | --------------------------- |
| Userli       | [http://userli.localhost](http://userli.localhost)                   | Main application            |
| Roundcube    | [http://roundcube.localhost](http://roundcube.localhost)             | Webmail client              |
| Keycloak     | [http://keycloak.localhost](http://keycloak.localhost)               | Single sign-on (optional)   |
| Mailcatcher  | [http://mailcatcher.localhost](http://mailcatcher.localhost)         | Catches all outgoing emails |
| Webhook test | [http://webhook.localhost](http://webhook.localhost)                 | Webhook endpoint testing    |

All web services are served by Caddy on port 80 and selected by host name. Browsers resolve `*.localhost`
to your machine, so no `/etc/hosts` entries are needed. On systems where `*.localhost` does not resolve
(for example Linux without `systemd-resolved`), add the names to `/etc/hosts`.

!!! tip "Port 80 is in use"
    Set another port before you start the environment, for example `export HTTP_PORT=8000`, and add it
    to the URLs: `http://userli.localhost:8000`.

## Requirements

- `podman` (or `docker`) with compose support
- Node.js 26 (LTS) with `npm`
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

1. loads sample data (users, domains, API tokens, ...) and the dev settings (`app_url`), and
2. starts Dovecot and Roundcube (`mail` profile).

When it finishes, open [http://userli.localhost](http://userli.localhost) and log in with `admin@example.org` / `password`.


!!! info
    The fixtures create user accounts (`admin`, `user`, `support` and `suspicious`, among others) on the domain `example.org`, all with the password `password`.
    They also create sample aliases and vouchers.
    See `src/DataFixtures` for details.

!!! info
    Dovecot and Roundcube are in the `mail` profile and must be started after the fixtures are loaded in the database, otherwise Dovecot will fail because the API token it is configured to use will not exists in the database. `make fixtures` handles both in the right order. 

!!! tip
    Run `make` without arguments to see all available targets.

### Keycloak

To test the [Keycloak integration](../operations/integrations/keycloak.md), run

```shell
make keycloak
```

This builds Keycloak with the latest release of the
[Userli user provider](https://github.com/systemli/userli-keycloak-provider) and imports the realm `userli`
(`sso` profile). The realm uses the dev environment as user federation, so you can sign in at
[http://keycloak.localhost/realms/userli/account](http://keycloak.localhost/realms/userli/account) with `user` / `password`.
The admin console is at [http://keycloak.localhost](http://keycloak.localhost) (`admin` / `admin`).

Load the fixtures first, because the realm uses their Keycloak API token.

To test an unreleased provider, build it in its repository and mount the jar in `docker-compose.override.yml`:

```yaml
services:
  keycloak:
    volumes:
      - ../userli-keycloak-provider/target/userli-storage-provider-1.0.0.jar:/opt/keycloak/providers/userli.jar:ro
```

## Next steps

* Get to know the code: [Architecture](architecture.md)
* Read the [contribution guidelines](contributing.md)
* Run the [tests](testing.md)
* Watch the frontend while you work:

    ```shell
    npm run watch
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
