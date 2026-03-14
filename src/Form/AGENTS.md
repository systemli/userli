# src/Form/ — Forms and Models

## Structure

```
Form/
├── Model/                  # Form DTOs — NEVER use entities
│   ├── Registration.php    # Registration flow data
│   ├── Password.php        # Password change
│   ├── AliasCreate.php     # Alias creation
│   ├── AliasAdminModel.php # Admin alias editing
│   ├── UserAdminModel.php  # Admin user editing
│   └── ...                 # RecoveryProcess, TwofactorConfirm, VoucherCreate, etc.
├── DataTransformer/        # Symfony data transformers
├── RegistrationType.php    # Registration form
├── PasswordType.php        # Password input with constraints
├── SettingsType.php        # Dynamic global settings form
├── UserAdminType.php       # Admin user edit form
└── ...                     # Additional form types
```

## Entity-Form Separation (MANDATORY)

**NEVER bind entities to forms.** Always use a dedicated model from `Model/`:

```php
// CORRECT
$registration = new Registration();  // Form model
$form = $this->createForm(RegistrationType::class, $registration);

// WRONG — never do this
$form = $this->createForm(UserType::class, $user);  // Entity bound to form
```

Form models are plain PHP classes with getters/setters + validation attributes. After form submission, data is transferred from model to entity in the Service.

## Form Type Conventions

- Named `*Type.php`, corresponding model in `Model/*.php`
- Validation via Symfony constraints on model properties, not in form type
- `PasswordType` and `PasswordConfirmationType` are reusable embedded types
- `SettingsType` is dynamically built from `config/settings.yaml` schema
- Autocomplete types (`DomainAutocompleteType`, `UserAutocompleteType`) use Symfony UX

## Data Transformers

Located in `DataTransformer/`. Used when form data needs transformation between view and model (e.g., domain name ↔ Domain entity).
