# AI Contribution Guidelines

## Project Overview

Userli is a Symfony-based web application for self-managing email users with mailbox encryption support (Dovecot).

- **Backend:** Symfony, Doctrine ORM, Twig, Monolog
- **Frontend:** Symfony UX, TailwindCSS, Heroicons
- **Admin:** Sonata Admin (`src/Admin/`)
- **Testing:** PHPUnit, Behat
- **Dev environment:** Podman and Docker (prefer Podman)

## Architecture Overview

### Entities (`src/Entity/`)

- `User`: Email account with roles, 2FA, and mailbox encryption
- `Domain`: Email domains with admins
- `Alias`: Email aliases pointing to users
- `Voucher`: Invite codes for registration (users get 3 after one week)
- `Setting`: Global settings, see `config/settings.yaml`
- `ReservedName`: Reserved email prefixes
- `OpenPgpKey`: OpenPGP public keys for WKD
- `ApiToken`: API authentication tokens with scopes
- `WebhookEndpoint`: Webhook target URLs with secrets
- `WebhookDelivery`: Webhook delivery log entries
- `UserNotification`: Admin notifications shown to users

### Key Patterns

- **Controllers** (`src/Controller/`): Separate GET/POST into distinct methods with explicit HTTP method constraints. See `RegistrationController` as canonical example.
- **Forms** (`src/Form/`): Symfony forms with dedicated models in `src/Form/Model/`. Never bind entities directly to forms.
- **Handlers** (`src/Handler/`): Business logic (e.g. `RegistrationHandler`, `MailCryptKeyHandler`)
- **Services** (`src/Service/`): Business logic (e.g. `UserResetService`, `WebhookDispatcher`)
- **Events** (`src/Event/`): Domain events dispatched via `EventDispatcherInterface` (e.g. `UserEvent::USER_CREATED`)
- **Enums** (`src/Enum/`): PHP enums for roles, webhook events, etc.
- **MessageHandlers** (`src/MessageHandler/`): Symfony Messenger handlers for async processing
- **Admin** (`src/Admin/`): Sonata Admin classes for backend management

### Roles (`src/Enum/Roles.php`)

`ROLE_USER`, `ROLE_ADMIN`, `ROLE_DOMAIN_ADMIN`, `ROLE_SUSPICIOUS`, `ROLE_SPAM`, `ROLE_PERMANENT`, `ROLE_MULTIPLIER`

## Project Structure

- `src/` - Application code (Controllers, Entities, Repositories, Handlers, Services, etc.)
- `templates/` - Twig templates
- `config/` - Configuration files (services, routes, packages, `settings.yaml`)
- `public/` - Publicly accessible files (entry point, assets)
- `default_translations/` - Translation files (EN and DE managed manually; other languages automated via Weblate)
- `tests/` - Unit tests (mirrors `src/` structure)
- `features/` - Behat feature files for functional testing
- `docs/` - Documentation (MkDocs with Material theme)
- `Dockerfile` & `docker-compose.yml` - Containerization and development environment

## Template Architecture

Three base templates in `templates/`:

- `base.html.twig`: Root layout with dark mode, assets, navbar
- `base_page.html.twig`: Full pages -- use blocks `page_title`, `page_subtitle`, `page_content`
- `base_step.html.twig`: Multi-step flows (registration, recovery) -- use blocks `step_icon`, `step_title`, `step_description`, `step_content`, `step_footer`

**Styling:** Tailwind CSS utility classes, Heroicons via `{{ ux_icon('heroicons:...') }}`. Form theme at `templates/Form/fields.html.twig`.

**Conventions:** Responsive mobile-first design, accessibility (ARIA attributes, semantic HTML), dark mode support.

## Security

- Use the `|safe_html` Twig filter (`src/Twig/SafeHtmlExtension.php`) for user content -- never use `|raw` for user-supplied data
- In JavaScript, use `sanitizeHTML()` (`assets/js/app.js`) before setting `innerHTML`
- Follow secure coding practices to prevent XSS, CSRF, injections, and auth bypasses

## PHP Conventions

Coding style is enforced by PHP CS Fixer, Rector, and Psalm. Use the commands below to format and check code. Additional conventions not covered by tooling:

- Inject environment variables via `#[Autowire(env: 'VAR_NAME')]` in constructors
- Use form models in `src/Form/Model/` instead of binding entities directly to forms

## Testing

- **PHPUnit** for unit tests, **Behat** for functional and behavior-driven scenarios
- Use `self::assert*` for assertions
- Use `$this->createMock(HttpClientInterface::class)` for HTTP client mocking
- All user-facing text must use the `|trans` filter with keys from `default_translations/`

## Contributing

- **Commits:** Use [Gitmojis](https://gitmoji.dev/) and write messages in English
- **Pull Requests:** Open as draft. Write the description in English with a summary of what was done and why (goal, improvement, feature, bug fix, etc.)

## Commands

```bash
# Development environment
podman compose up -d
podman compose exec -it userli <command>

# Dependencies
composer install --no-scripts
yarn install

# Assets
yarn encore dev

# Database fixtures
bin/console doctrine:fixtures:load

# Code quality (w/o podman)
composer cs-fix          # Fix code style (PHP CS Fixer)
composer cs-check        # Check code style (dry-run)
composer rector-fix      # Apply Rector refactorings
composer rector-check    # Check Rector (dry-run)
composer psalm           # Run Psalm static analysis

# Tests (w/o podman)
bin/phpunit                    # Unit tests
bin/behat --format progress    # Functional / BDD scenarios
```
