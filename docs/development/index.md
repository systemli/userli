# Getting started

Userli is a Symfony-based web application for self-managing email users with mailbox encryption support (Dovecot).
It is built with PHP, Symfony, Doctrine ORM, and uses TailwindCSS with Symfony UX on the frontend.

We provide a `docker-compose.yml` that starts Userli with MariaDB, Dovecot, Roundcube, Mailcatcher, Redis, and a webhook tester to set up a complete development environment.

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

Start the containers:

=== "podman"

    ```shell
    podman compose up -d
    ```

=== "docker"

    ```shell
    docker compose up -d
    ```

!!! info
    This will build the containers on the first run. Append `--build` to force a full rebuild.

Install PHP dependencies and build frontend assets:

```shell
make assets
```

!!! tip
    Run `make` without arguments to see all available targets.

Initialize the database:

=== "podman"

    ```shell
    podman compose exec userli bin/console doctrine:migrations:migrate --no-interaction
    ```

=== "docker"

    ```shell
    docker compose exec userli bin/console doctrine:migrations:migrate --no-interaction
    ```

Load sample data:

=== "podman"

    ```shell
    podman compose exec userli bin/console doctrine:fixtures:load
    ```

=== "docker"

    ```shell
    docker compose exec userli bin/console doctrine:fixtures:load
    ```

!!! info
    The fixtures create some user accounts (`admin`, `user`, `support` and `suspicious`, among others) on the domain `example.org`, all with the password `password`.
    They also create sample aliases and vouchers.
    See `src/DataFixtures` for details.

Open your browser and go to [http://localhost:8000](http://localhost:8000).

## Project structure

```text
src/
├── Admin/            Sonata Admin classes (backend management)
├── Controller/       HTTP controllers (separate GET/POST methods)
├── Entity/           Doctrine entities (User, Domain, Alias, Voucher, …)
├── Enum/             PHP enums (Roles, webhook events, …)
├── Event/            Domain events dispatched via EventDispatcher
├── EventListener/    Symfony event subscribers
├── Form/             Symfony form types
│   └── Model/        Form data models (never bind entities directly)
├── Handler/          Business logic (registration, mail encryption, …)
├── MessageHandler/   Symfony Messenger async handlers
├── Repository/       Doctrine repositories
├── Schedule/         Symfony Scheduler definitions
├── Security/         Authentication and authorization
├── Service/          Business services (UserResetService, WebhookDispatcher, …)
├── Twig/             Twig extensions and filters
└── Validator/        Custom validation constraints
```

### Key entities

| Entity              | Purpose                                            |
| ------------------- | -------------------------------------------------- |
| `User`              | Email account with roles, 2FA, mailbox encryption  |
| `Domain`            | Email domain with admin assignments                |
| `Alias`             | Email alias pointing to a user                     |
| `Voucher`           | Invite code for registration                       |
| `Setting`           | Global application settings (see `config/settings.yaml`) |
| `ReservedName`      | Reserved email prefixes                            |
| `OpenPgpKey`        | OpenPGP public keys for WKD                        |
| `ApiToken`          | API authentication token with scopes               |
| `WebhookEndpoint`   | Webhook target URL with secret                     |

### Key patterns

- **Controllers** separate GET and POST into distinct methods with explicit HTTP method constraints.
  See `RegistrationController` as a reference.
- **Form models** in `src/Form/Model/` are used instead of binding entities directly to forms.
- **Domain events** (e.g. `UserEvent::USER_CREATED`) are dispatched via `EventDispatcherInterface`.
- **Roles** are defined in `src/Enum/Roles.php`: `ROLE_USER`, `ROLE_ADMIN`, `ROLE_DOMAIN_ADMIN`, `ROLE_SUSPICIOUS`, `ROLE_SPAM`, `ROLE_PERMANENT`, `ROLE_MULTIPLIER`.

### Templates

Three base templates exist:

| Template                | Use case                                    | Key blocks                                      |
| ----------------------- | ------------------------------------------- | ------------------------------------------------ |
| `base.html.twig`        | Root layout (dark mode, assets, navbar)     | —                                                |
| `base_page.html.twig`   | Full pages                                  | `page_title`, `page_subtitle`, `page_content`    |
| `base_step.html.twig`   | Multi-step flows (registration, recovery)   | `step_icon`, `step_title`, `step_description`, `step_content`, `step_footer` |

Styling uses Tailwind CSS utility classes.
Icons use [Heroicons](https://heroicons.com/) via Symfony UX Icons:

```twig
{{ ux_icon('heroicons:arrow-left', {class: 'size-5'}) }}
```

## Background services

The development environment runs three containers for the Userli application:

| Container          | Purpose                                           |
| ------------------ | ------------------------------------------------- |
| `userli`           | Web server (Apache + PHP)                         |
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
