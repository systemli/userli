# Architecture

Userli is a [Symfony](https://symfony.com/) 7.4 application written in PHP
8.4. It uses [Doctrine ORM](https://www.doctrine-project.org/) with MariaDB,
Twig templates with [Tailwind CSS](https://tailwindcss.com/) and
[Stimulus](https://stimulus.hotwired.dev/) controllers written in TypeScript,
bundled with Webpack Encore.

The repository contains `AGENTS.md` files with detailed conventions per
directory. They are written for AI coding assistants, but are a good reference
for humans, too.

## Project structure

```text
userli/
├── src/                    Application code
├── templates/              Twig templates
├── assets/                 Stimulus controllers, Tailwind CSS, TypeScript
├── config/                 Symfony configuration, routes, settings.yaml
├── migrations/             Doctrine migrations
├── default_translations/   Translations (EN/DE manual, others via Weblate)
├── tests/                  PHPUnit and Vitest tests, mirrors src/ and assets/
├── features/               Behat scenarios
├── contrib/                Dovecot Lua adapter, reserved names list
├── docker/                 Container images and configuration for development
└── docs/                   This documentation (MkDocs)
```

```text
src/
├── Command/          Console commands (app:*)
├── Controller/       HTTP controllers
│   ├── Account/      User self-service
│   ├── Admin/        Admin interface
│   └── Api/          APIs for Dovecot, Postfix, Keycloak, Roundcube, Retention
├── DataFixtures/     Example data for development
├── Entity/           Doctrine entities (User, Domain, Alias, Voucher, …)
├── EntityListener/   Doctrine lifecycle listeners (cache invalidation, …)
├── Enum/             PHP enums (Roles, ApiScope, webhook events, cache keys, …)
├── Event/            Domain events
├── EventListener/    Symfony event listeners
├── Form/             Symfony form types
│   └── Model/        Form data models (never bind entities directly)
├── Handler/          Business logic (registration, MailCrypt, deletion, …)
├── Mail/             Mailers for user-facing emails
├── Message/          Messenger messages (async jobs)
├── MessageHandler/   Handlers for messages, 1:1 with Message/
├── Repository/       Doctrine repositories
├── Schedule/         Scheduler definition (maintenance tasks)
├── Security/         Authenticators, user provider, user checker
├── Service/          Business services (managers, webhook dispatcher, …)
├── Traits/           Entity composition traits
├── Twig/             Twig extensions and filters
├── Validator/        Custom constraints and validators
└── Voter/            Authorization voters
```

## Key concepts

### Entities

| Entity | Purpose |
|--------|---------|
| `User` | Email account with roles, password hash, 2FA, MailCrypt keys and recovery token. Composed of traits in `src/Traits/`. |
| `Domain` | Mail domain with embedded `InvitationSettings`. |
| `Alias` | Alias address, random or custom, pointing to a user or a destination. |
| `Voucher` | Invite code. |
| `ReservedName` | Blocked local parts. |
| `OpenPgpKey` | Published OpenPGP key for the Web Key Directory. |
| `ApiToken` | Hashed API token with scopes. |
| `WebhookEndpoint`, `WebhookDelivery` | Webhook configuration and delivery log. |
| `UserNotification` | Security notices for users. |
| `Setting` | Runtime settings, schema in `config/settings.yaml`. |

### Domain scoping

For every logged-in user who is not `ROLE_ADMIN`, a Doctrine SQL filter
(`src/Entity/Filter/DomainFilter.php`) limits all queries on domain-aware
entities to the user's own domain. This is how domain admins only see their
domain. Voters (`src/Voter/`) add resource-level checks.

### Events, messages and the scheduler

* **Domain events** (`src/Event/`, e.g. `UserEvent::USER_CREATED`) are
  dispatched synchronously via the `EventDispatcher` and handled by listeners
  in `src/EventListener/` – for example to send the welcome email or
  webhooks.
* **Messages** (`src/Message/`) are processed asynchronously by the worker
  (`messenger:consume async`). Every message has exactly one handler in
  `src/MessageHandler/`. Messages are signed.
* **Scheduled tasks** are defined in `src/Schedule/MaintenanceSchedule.php`.
  The scheduler dispatches messages that are then processed by the worker.

### Settings

Settings that admins can change at runtime are defined in
`config/settings.yaml` (type, default, validation, form options) and stored in
the `Setting` entity. Read them in PHP via the settings service and in Twig
with `setting('name')`. Environment variables are reserved for
infrastructure configuration.

### Caching

API lookups (Dovecot, Postfix, WKD, MTA-STS) are cached. Cache keys are
defined as enums in `src/Enum/*CacheKey.php`. Entity listeners in
`src/EntityListener/` invalidate them on changes.

### MailCrypt

`src/Handler/MailCryptKeyHandler.php` creates the key pairs (via `openssl`)
and encrypts the private key with libsodium. See
[MailCrypt](../operations/mailcrypt.md#implementation-details).

## Conventions

* **Controllers** separate GET and POST into distinct methods with explicit
  `methods` in the `#[Route]` attribute. `RegistrationController` is the
  canonical example.
* **Forms** never bind entities. Use models in `src/Form/Model/`.
* **Environment variables** are injected with `#[Autowire(env: 'VAR_NAME')]`.
* **Translations:** all user-facing text uses the `|trans` filter, see
  [Translations](translations.md).
* **Security in templates:** never use `|raw` for user-supplied data – use
  `|safe_html`. Never set `innerHTML` with unsanitized content.
* **No type error suppression** (`@psalm-suppress`, `@ts-ignore`, `as any`).

## Templates

Three base templates exist:

| Template                | Use case                                    | Key blocks                                      |
| ----------------------- | ------------------------------------------- | ------------------------------------------------ |
| `base.html.twig`        | Root layout (dark mode, assets, navbar)     | —                                                |
| `base_page.html.twig`   | Full pages                                  | `page_title`, `page_subtitle`, `page_content`    |
| `base_step.html.twig`   | Multi-step flows (registration, recovery)   | `step_icon`, `step_title`, `step_description`, `step_content`, `step_footer` |

Styling uses Tailwind CSS utility classes, with dark mode and mobile-first
responsive layouts. Icons use [Heroicons](https://heroicons.com/) via Symfony
UX Icons:

```twig
{{ ux_icon('heroicons:arrow-left', {class: 'size-5'}) }}
```

Stimulus controllers live in `assets/controllers/` and are written in
TypeScript.
