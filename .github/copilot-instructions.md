# Copilot Instructions for Userli

Userli is a Symfony-based web application for self-managing email users with mailbox encryption support (Dovecot).

## Architecture Overview

**Core Domain Entities** (in `src/Entity/`):

- `User`: Email account with roles (`ROLE_USER`, `ROLE_ADMIN`, `ROLE_SUSPICIOUS`, `ROLE_SPAM`), 2FA, and mailbox encryption
- `Domain`: Email domains with admins
- `Alias`: Email aliases pointing to users
- `Voucher`: Invite codes for registration (users get 3 after one week)
- `Setting`: Global settings, see `config/settings.yaml`

**Key Patterns**:

- **Controllers** (`src/Controller/`): Handle HTTP requests, delegate to services
- **Forms** (`src/Form/`): Symfony forms with models in `src/Form/Model/`
- **Repositories** (`src/Repository/`): Data access via Doctrine ORM
- **Handlers** (`src/Handler/`): Business logic (deprecated, migrating to services)
- **Services** (`src/Service/`): Business logic
- **Events**: Domain events dispatched via `EventDispatcherInterface` (e.g., `UserEvent::USER_CREATED`)
- **Admin**: Sonata Admin for backend management in `src/Admin/` (deprecated, migrating to custom logic)

## Development Commands

```bash
# Start dev environment (prefer podman)
podman compose up -d
podman compose exec -it userli <command>

# Install dependencies
composer install --no-scripts && yarn install

# Build assets
yarn encore dev

# Run tests (requires fixtures first)
bin/phpunit                    # Unit/functional tests
bin/behat --format progress    # BDD scenarios

# Code quality
php-cs-fixer fix src --rules=@Symfony
```

## Security: XSS Prevention

Use the custom `|safe_html` Twig filter (`src/Twig/SafeHtmlExtension.php`) for user content:

```twig
{# ❌ DANGEROUS #}  {{ user_content|raw }}
{# ✅ SAFE #}       {{ user_content|safe_html }}
{# ✅ OK for static translations #}  {{ "key.with.html"|trans|raw }}
```

In JavaScript, use `sanitizeHTML()` before setting `innerHTML`.

## Template Architecture

Three base templates in `templates/`:

- `base.html.twig`: Root with dark mode, assets
- `base_page.html.twig`: Full pages - use `{% block page_title %}`, `{% block page_content %}`
- `base_step.html.twig`: Multi-step flows (registration, recovery) - use `{% block step_title %}`, `{% block step_content %}`

**Styling**: Tailwind CSS utility classes, Heroicons via `{{ ux_icon('heroicons:...') }}`. Form theme at `templates/Form/fields.html.twig`.

**Conventions**: Responsive design with mobile-first approach, acccessibility (ARIA attributes, semantic HTML) and dark mode support.

## Controller Pattern

Separate GET/POST into distinct methods with explicit HTTP method constraints:

```php
#[Route('/register', name: 'register', methods: ['GET'])]
public function show(): Response { /* display form */ }

#[Route('/register', name: 'register_submit', methods: ['POST'])]
public function submit(Request $request): Response { /* process form */ }
```

See `src/Controller/RegistrationController.php` for the canonical example.

## PHP Conventions

- `declare(strict_types=1);` in all files
- `@Symfony` CS Fixer rules, 4-space indentation
- Constructor property promotion with `readonly`
- Env vars via `#[Autowire(env: 'VAR_NAME')]`
- Form models in `src/Form/Model/`, not entities directly

## Testing

- PHPUnit in `tests/` mirrors `src/` structure
- Behat features in `features/` for user flows
- Use `self::assert*` or `$this->assert*`
- Mock services, not HTTP responses (`MockHttpClient` over response mocking)

## Translations

All user-facing text via `{{ 'key'|trans }}`. Translations in `default_translations/`.
Other languages than English and German are automated via Weblate.
