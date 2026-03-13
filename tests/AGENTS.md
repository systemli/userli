# tests/ — Testing Infrastructure

200+ files mirroring `src/` structure. Three test frameworks: PHPUnit, Behat, Vitest.

## Structure

```
tests/
├── Command/           # 17 command tests
├── Controller/        # (via Behat features/ instead)
├── Creator/           # Creator tests
├── DependencyInjection/ # Configuration tests
├── Dto/               # DTO tests
├── Entity/            # Entity unit tests
├── EntityListener/    # 7 Doctrine listener tests
├── Enum/              # Enum tests
├── Event/             # Event tests
├── EventListener/     # 16 event listener tests
├── Form/              # 26 form type tests
├── Functional/        # Functional integration tests
├── Guesser/           # Guesser tests
├── Handler/           # 12 handler tests
├── Helper/            # Helper tests
├── Importer/          # Importer tests
├── Integration/       # Integration tests
├── Mail/              # Mail tests
├── MessageHandler/    # 15 message handler tests
├── Model/             # Model tests
├── Schedule/          # Schedule tests
├── Security/          # Auth tests (ApiTokenAuthenticator, UserChecker, etc.)
├── Service/           # 20 service tests
├── Twig/              # Twig extension tests
├── Validator/         # 10 validator tests
├── Voter/             # Voter tests
├── Behat/             # Behat contexts
│   ├── FeatureContext.php  # Main context (1132 lines — monolithic)
│   └── ApiContext.php      # API testing context
├── js/                # Vitest JS/TS tests
│   └── controllers/   # 10 Stimulus controller tests
├── autoload.php       # Test autoloader (dg/bypass-finals)
├── bootstrap.php      # PHPUnit bootstrap
└── dovecot-api-mock.py # Mock Dovecot API for integration tests
```

## PHPUnit

- Config: `phpunit.xml.dist`
- Bootstrap: `tests/bootstrap.php`
- Assertions: Use `self::assert*` (not `$this->assert*`)
- Mocking: `$this->createMock()` — `dg/bypass-finals` allows mocking `final` classes
- HTTP client mocking: `$this->createMock(HttpClientInterface::class)`
- Tests mirror `src/` exactly: `src/Handler/RegistrationHandler.php` → `tests/Handler/RegistrationHandlerTest.php`

## Behat

- Config: `behat.yml.dist`
- Features: `features/` (30 `.feature` files)
- Main context: `tests/Behat/FeatureContext.php` — handles all web scenarios
- API context: `tests/Behat/ApiContext.php` — API-specific steps
- Uses BrowserKit driver, no real browser needed
- Feature naming: `admin_*.feature` (admin), `api_*.feature` (API), `*.feature` (user-facing)

## Vitest (JS/TS)

- Config: `vitest.config.ts`
- Tests: `tests/js/controllers/` (10 Stimulus controller tests)
- Run: `yarn test`

## Special Files

- `tests/autoload.php`: Registers `dg/bypass-finals` to allow mocking final Symfony classes
- `tests/dovecot-api-mock.py`: Python mock server for Dovecot API integration tests
- `tests/mailcrypt_integration.sh`: Shell script for mailbox encryption E2E tests

## Anti-Patterns

- **NEVER** delete failing tests to make CI pass
- **NEVER** use `$this->assert*` — always `self::assert*`
- **NEVER** skip adding test when creating new Handler/Service/Command
