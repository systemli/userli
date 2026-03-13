# src/ — Application Code

327 PHP files across 33 subdirectories. Standard Symfony structure with project-specific conventions.

## Directory Map

| Directory | Files | Purpose |
|-----------|-------|---------|
| `Controller/` | 32 | HTTP layer — 3 subdirs: Account/, Admin/, Api/ (see Controller/AGENTS.md) |
| `Form/` | 50 | Symfony forms + Model/ DTOs (see Form/AGENTS.md) |
| `Traits/` | 27 | Entity composition — User entity uses all 27 |
| `Validator/` | 22 | Custom constraints: 11 Constraint + Validator pairs |
| `Service/` | 20 | Domain services and entity managers |
| `Command/` | 18 | Console commands, all extend `AbstractUsersCommand` base |
| `EventListener/` | 16 | Symfony event listeners (login, locale, webhooks, etc.) |
| `Message/` | 15 | Symfony Messenger messages — async job definitions |
| `MessageHandler/` | 15 | Messenger handlers — 1:1 with Message/ (always paired) |
| `Entity/` | 13 | Doctrine entities — heavily trait-composed |
| `Repository/` | 12 | Doctrine repositories |
| `Handler/` | 12 | Business logic operations (registration, recovery, crypto) |
| `Enum/` | 10 | PHP enums: Roles, ApiScope, CacheKeys, MailCrypt, etc. |
| `DataFixtures/` | 10 | Test data fixtures |
| `EntityListener/` | 7 | Doctrine lifecycle listeners (cache invalidation, timestamps) |
| `Event/` | 7 | Domain events: User, Alias, Domain, Login, Notification |
| `Exception/` | 7 | Custom exception classes |
| `Security/` | 6 | Auth: ApiTokenAuthenticator, UserChecker, UserProvider, RequireApiScope |
| `Helper/` | 6 | Utility classes |
| `Dto/` | 6 | Data transfer objects |
| `Twig/` | 3 | Twig extensions: SafeHtmlExtension, SettingsExtension |
| `Mail/` | 3 | Mail builder/sender utilities |
| `Voter/` | 2 | AliasVoter, DomainVoter — resource-level authorization |
| `Model/` | 2 | Domain model classes |
| `Importer/` | 2 | GPG key import utilities |
| `Creator/` | 2 | Factory-like creators |
| `Schedule/` | 1 | MaintenanceSchedule — Symfony Scheduler cron jobs |
| `Remover/` | 1 | Entity removal logic |
| `Guesser/` | 1 | Domain guesser |
| `Factory/` | 1 | Entity factory |
| `DependencyInjection/` | 1 | Settings configuration |

## Key Patterns

### Handlers vs Services

- **Handlers** (`Handler/`): Stateless operations on entities. Named by action: `RegistrationHandler`, `RecoveryHandler`, `MailCryptKeyHandler`. Called from controllers.
- **Services** (`Service/`): Entity managers and domain services. Named `*Manager` (CRUD wrappers) or `*Service` (cross-cutting). Called from handlers, controllers, commands.

### Entity Composition via Traits

Entities in `Entity/` are thin — most properties come from `Traits/`. `User` uses all 27 traits. When adding a field to an entity, check if an existing trait provides it or create a new one.

### Message/MessageHandler Pairing

Every file in `Message/` has a matching handler in `MessageHandler/`. Example: `SendWebhook.php` → `SendWebhookHandler.php`. Never create one without the other.

### Validator Pairs

Every constraint in `Validator/` is paired: `EmailAvailable.php` (constraint attribute) + `EmailAvailableValidator.php` (logic). 11 pairs covering email rules, passwords, vouchers, TOTP.

### EntityListener Cache Invalidation

`EntityListener/` contains Doctrine lifecycle listeners that dispatch Messenger messages to invalidate caches. Pattern: entity change → listener → Message → MessageHandler → cache clear.

### Console Commands

18 commands in `Command/`. Many extend `AbstractUsersCommand` which provides domain filtering and batch user operations. Commands are prefixed: `app:users:*`, `app:voucher:*`, `app:domain:*`, `app:admin:*`, `app:api-token:*`.

## Anti-Patterns

- **NEVER** put business logic in controllers — use Handler/ or Service/
- **NEVER** access entity properties directly from outside — use repository methods
- **NEVER** create a Message without a matching MessageHandler
- **NEVER** add entity properties inline — use or create a Trait
