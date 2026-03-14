# src/Controller/ — HTTP Layer

Three subdirectories + root controllers.

## Structure

```
Controller/
├── Account/              # Authenticated user self-service
│   ├── AccountController.php     # Password change, recovery token, delete account
│   ├── AliasController.php       # Custom + random alias management
│   ├── OpenPGPController.php     # OpenPGP key upload/delete
│   ├── TwofactorController.php   # TOTP setup + backup codes
│   └── VoucherController.php     # View/create invite vouchers
├── Admin/                # Admin panel
│   ├── DashboardController.php   # Admin dashboard
│   ├── UserController.php        # User CRUD
│   ├── DomainController.php      # Domain management
│   ├── AliasController.php       # Alias admin
│   ├── SettingsController.php    # Global settings editor
│   ├── MaintenanceController.php # Maintenance operations
│   └── ...                       # Voucher, ApiToken, Webhook, ReservedName, etc.
├── Api/                  # API endpoints
│   ├── DovecotController.php     # Dovecot mailbox auth + encryption
│   ├── PostfixController.php     # Postfix mail routing
│   ├── KeycloakController.php    # Keycloak SSO integration
│   ├── RoundcubeController.php   # Roundcube webmail integration
│   ├── RetentionController.php   # Data retention API
│   ├── WkdController.php         # Web Key Directory (OpenPGP)
│   └── MtaStsController.php      # MTA-STS policy
├── InitController.php            # First-run setup
├── RecoveryController.php        # Account recovery flow
├── RegistrationController.php    # Registration (canonical GET/POST pattern)
├── SecurityController.php        # Login/logout
└── StartController.php           # Landing page
```

## GET/POST Split Pattern (MANDATORY)

Controllers use **separate methods** for GET and POST with explicit HTTP method constraints. `RegistrationController` is the canonical reference:

```php
#[Route(path: '/register/{voucher}', name: 'register_voucher', methods: ['GET'])]
public function show(string $voucher): Response { /* render form */ }

#[Route(path: '/register', name: 'register_submit', methods: ['POST'])]
public function submit(Request $request): Response { /* handle submission */ }
```

- GET method: Creates form, renders template
- POST method: Handles request, validates, delegates to Service
- Separate route names: `*_show` / `*_submit` or similar

## Controller Conventions

- Controllers are `final` classes extending `AbstractController`
- Constructor injection via `readonly` promoted properties
- Business logic delegated to `Service/` — controllers only coordinate
- Flash messages use translation keys: `$this->addFlash('error', 'flashes.voucher-invalid')`
- Forms created with `$this->createForm()`, specify `action` and `method` in options

## Admin Controllers

- Role-gated: `ROLE_ADMIN` or `ROLE_DOMAIN_ADMIN`
- Located in `Admin/` subdirectory
- Use Twig templates from `templates/Admin/`
- `DomainSearchController` and `UserSearchController` provide AJAX search endpoints

## API Controllers

- Token auth via `ApiTokenAuthenticator`
- Scope-based access control: `#[RequireApiScope(ApiScope::DOVECOT)]`
- Return JSON responses
- No Twig rendering — pure data endpoints
