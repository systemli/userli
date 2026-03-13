# AI Contribution Guidelines

**Generated:** 2026-03-13
**Commit:** b7aba4ad
**Branch:** main

## Overview

Userli — Symfony 7.4 web app for self-managing email users with Dovecot mailbox encryption. PHP 8.4, Doctrine ORM, TailwindCSS, Sonata Admin.

## Structure

```
userli/
├── src/                    # Application code (see src/AGENTS.md)
│   ├── Controller/         # HTTP layer (see src/Controller/AGENTS.md)
│   ├── Entity/             # 11 Doctrine entities, trait-composed
│   ├── Form/               # Symfony forms + models (see src/Form/AGENTS.md)
│   ├── Handler/            # Business logic operations
│   ├── Service/            # Managers + domain services
│   ├── EventListener/      # Symfony event listeners
│   ├── Message/            # Messenger messages (async)
│   ├── MessageHandler/     # Messenger handlers (1:1 with messages)
│   ├── Command/            # 18 console commands
│   ├── Validator/          # Custom constraint + validator pairs
│   ├── Traits/             # 27 reusable entity traits
│   ├── Enum/               # PHP enums (roles, scopes, cache keys)
│   └── Security/           # Authenticator, provider, voter
├── templates/              # Twig templates (see templates/AGENTS.md)
├── config/                 # Symfony config, routes, settings.yaml
├── tests/                  # Tests mirror src/ (see tests/AGENTS.md)
├── features/               # 30 Behat feature files
├── assets/                 # Stimulus controllers, TailwindCSS, JS
├── default_translations/   # EN/DE manual, others via Weblate
├── migrations/             # 15 Doctrine migrations
└── docs/                   # MkDocs documentation site
```

## Where to Look

| Task | Location | Notes |
|------|----------|-------|
| Add user-facing feature | `src/Controller/`, `src/Form/`, `templates/` | GET/POST split pattern |
| Add admin feature | `src/Controller/Admin/` | Role-gated, Twig admin templates |
| Add API endpoint | `src/Controller/Api/` | Token auth, scope-based |
| Business logic | `src/Handler/` or `src/Service/` | Handlers = operations, Services = managers |
| Background job | `src/Message/` + `src/MessageHandler/` | Always paired 1:1 |
| Scheduled task | `src/Schedule/MaintenanceSchedule.php` | Symfony Scheduler |
| Custom validation | `src/Validator/` | Constraint + Validator pair |
| Entity change | `src/Entity/` + `src/Traits/` | Entities compose many traits |
| Cache invalidation | `src/EntityListener/` | Doctrine lifecycle listeners |
| Domain events | `src/Event/` + `src/EventListener/` | Dispatched via EventDispatcher |
| Email templates | `templates/Email/` | Twig email templates |
| Settings/config | `config/settings.yaml` | Dynamic settings schema |
| Translations | `default_translations/` | EN + DE only, `|trans` filter |

## Entities

- `User`: Email account — roles, 2FA (TOTP + backup codes), mailbox encryption, composed of 27 traits
- `Domain`: Email domains with admin relationships
- `Alias`: Email aliases → users (random + custom)
- `Voucher`: Invite codes for registration (users get 3 after one week)
- `Setting`: Global settings persisted to DB, schema in `config/settings.yaml`
- `ReservedName`: Reserved email prefixes (blocked from registration)
- `OpenPgpKey`: OpenPGP public keys for WKD (Web Key Directory)
- `ApiToken`: API auth tokens with `ApiScope` enum scopes
- `WebhookEndpoint` / `WebhookDelivery`: Webhook targets + delivery log
- `UserNotification`: Admin-created notifications shown to users

## Roles (`src/Enum/Roles.php`)

`ROLE_USER`, `ROLE_ADMIN`, `ROLE_DOMAIN_ADMIN`, `ROLE_SUSPICIOUS`, `ROLE_SPAM`, `ROLE_PERMANENT`, `ROLE_MULTIPLIER`

## Conventions

- **Controller GET/POST split**: Separate methods with explicit `#[Route(methods: ['GET'])]` / `#[Route(methods: ['POST'])]`. `RegistrationController` is canonical example.
- **Form models**: NEVER bind entities to forms. Use dedicated models in `src/Form/Model/`.
- **Environment variables**: Inject via `#[Autowire(env: 'VAR_NAME')]` in constructors.
- **Coding style**: PHP CS Fixer + Rector + Psalm enforced. Run `composer cs-fix && composer rector-fix && composer psalm`.
- **Translations**: All user-facing text uses `|trans` filter with keys from `default_translations/`. EN/DE managed manually.
- **Commit messages**: Gitmoji prefix, English language.
- **PRs**: Open as draft, English description with goal/improvement summary.

## Anti-Patterns (This Project)

- **NEVER** use `|raw` for user-supplied data in Twig — use `|safe_html` filter (`src/Twig/SafeHtmlExtension.php`)
- **NEVER** set `innerHTML` without `sanitizeHTML()` (`assets/js/app.js`)
- **NEVER** bind entities directly to Symfony forms — always use `src/Form/Model/`
- **NEVER** suppress type errors (`@ts-ignore`, `as any`, psalm suppression)
- **NEVER** create Message without matching MessageHandler (always paired 1:1)

## Security

- API auth: `ApiTokenAuthenticator` with scope-based access (`ApiScope` enum, `RequireApiScope` attribute)
- User checks: `UserChecker` validates account status before auth
- Voters: `AliasVoter`, `DomainVoter` for resource-level auth
- Twig: `|safe_html` filter for user content, never `|raw`
- JS: `sanitizeHTML()` before `innerHTML`
- CSRF: Symfony CSRF protection on all forms

## Frontend

- **TailwindCSS** for styling, Webpack Encore for bundling
- **Stimulus controllers** in `assets/controllers/` (TypeScript)
- **Heroicons** via `{{ ux_icon('heroicons:icon-name') }}`
- **Vitest** for JS/TS unit tests in `tests/js/`
- Mobile-first responsive, dark mode support, ARIA accessibility

## Commands

```bash
# Dev environment (prefer Podman)
podman compose up -d
podman compose exec -it userli <command>

# Dependencies
composer install --no-scripts
yarn install

# Assets
yarn encore dev

# Database
bin/console doctrine:fixtures:load

# Code quality
composer cs-fix          # PHP CS Fixer
composer cs-check        # Dry-run
composer rector-fix      # Rector refactorings
composer rector-check    # Dry-run
composer psalm           # Static analysis

# Tests
bin/phpunit                    # Unit tests
bin/behat --format progress    # Behat scenarios
yarn test                      # JS/TS unit tests
```

## CI/CD

9 GitHub Actions workflows:
- `integration.yml` — PHPUnit + Behat + code quality
- `security-check.yml` — Dependency vulnerability scan
- `codeql.yml` — CodeQL static analysis
- `psalm.yml` — Psalm type checking
- `rector.yml` — Rector dry-run
- `migrations.yml` — Database migration validation
- `mailcrypt.yml` — Mailbox encryption integration tests
- `composer-update.yml` — Automated dependency updates
- `mkdocs.yml` — Documentation build

## Notes

- `config/reference.php` (2179 lines) — largest file, auto-generated settings reference
- `tests/Behat/FeatureContext.php` (1132 lines) — monolithic Behat context, main BDD entry point
- Docker Compose uses MariaDB + Postfix for full email stack testing
- `dg/bypass-finals` in dev deps — allows mocking final classes in tests
