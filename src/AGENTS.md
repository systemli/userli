# src/ — Application Code

Standard Symfony structure with project-specific conventions.

## Directory Map

| Directory | Purpose |
|-----------|---------|
| `Controller/` | HTTP layer — 3 subdirs: Account/, Admin/, Api/ (see Controller/AGENTS.md) |
| `Form/` | Symfony forms + Model/ DTOs (see Form/AGENTS.md) |
| `Traits/` | Entity composition traits |
| `Validator/` | Custom constraints: Constraint + Validator pairs |
| `Service/` | Domain services, entity managers, and business logic |
| `Command/` | Console commands, many extend `AbstractUsersCommand` base |
| `EventListener/` | Symfony event listeners (login, locale, webhooks, etc.) |
| `Message/` | Symfony Messenger messages — async job definitions |
| `MessageHandler/` | Messenger handlers — 1:1 with Message/ (always paired) |
| `Entity/` | Doctrine entities — trait-composed |
| `Repository/` | Doctrine repositories |
| `Handler/` | Legacy business logic (being migrated to Service/) |
| `Enum/` | PHP enums: Roles, ApiScope, CacheKeys, MailCrypt, etc. |
| `DataFixtures/` | Test data fixtures |
| `EntityListener/` | Doctrine lifecycle listeners (cache invalidation, timestamps) |
| `Event/` | Domain events: User, Alias, Domain, Login, Notification |
| `Exception/` | Custom exception classes |
| `Security/` | Auth: ApiTokenAuthenticator, UserChecker, UserProvider, RequireApiScope |
| `Helper/` | Utility classes |
| `Dto/` | Data transfer objects |
| `Twig/` | Twig extensions: SafeHtmlExtension, SettingsExtension |
| `Mail/` | Mail builder/sender utilities |
| `Voter/` | AliasVoter, DomainVoter — resource-level authorization |
| `Model/` | Domain model classes |
| `Importer/` | GPG key import utilities |
| `Creator/` | Factory-like creators |
| `Schedule/` | MaintenanceSchedule — Symfony Scheduler cron jobs |
| `Remover/` | Entity removal logic |
| `Guesser/` | Domain guesser |
| `Factory/` | Entity factory |
| `DependencyInjection/` | Settings configuration |

## Key Patterns

### Services

`Service/` is the primary location for business logic. Named `*Manager` (CRUD wrappers) or `*Service` (cross-cutting). Called from controllers, commands, and other services.

`Handler/` exists but is deprecated — handlers are being migrated into `Service/`. New business logic should go in `Service/`.

### Entity Composition via Traits

Entities in `Entity/` are thin — most properties come from `Traits/`.

### Message/MessageHandler Pairing

Every file in `Message/` has a matching handler in `MessageHandler/`. Example: `SendWebhook.php` → `SendWebhookHandler.php`. Never create one without the other.

### Validator Pairs

Every constraint in `Validator/` is paired: `EmailAvailable.php` (constraint attribute) + `EmailAvailableValidator.php` (logic). Pairs cover email rules, passwords, vouchers, TOTP.

### EntityListener Cache Invalidation

`EntityListener/` contains Doctrine lifecycle listeners that dispatch Messenger messages to invalidate caches. Pattern: entity change → listener → Message → MessageHandler → cache clear.

### Console Commands

Commands in `Command/`. Many extend `AbstractUsersCommand` which provides domain filtering and batch user operations. Commands are prefixed: `app:users:*`, `app:voucher:*`, `app:domain:*`, `app:admin:*`, `app:api-token:*`.

## Anti-Patterns

- **NEVER** put business logic in controllers — use `Service/`
- **NEVER** access entity properties directly from outside — use repository methods
- **NEVER** create a Message without a matching MessageHandler
