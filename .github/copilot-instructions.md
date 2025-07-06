# Copilot Instructions for Userli Project

## Table of Contents

1. [Security Guidelines](#security-guidelines)
2. [Template Architecture](#template-architecture)
3. [Twig Development Guidelines](#twig-development-guidelines)
4. [JavaScript Guidelines](#javascript-guidelines)
5. [Symfony Development Guidelines](#symfony-development-guidelines)
6. [Code Quality Standards](#code-quality-standards)
7. [Best Practices](#best-practices)

## Security Guidelines

### HTML Sanitization and XSS Prevention

This project uses DOMPurify for client-side HTML sanitization to prevent XSS attacks:

#### In Twig Templates

- **NEVER use `|raw` filter** for user-generated or dynamic content
- **USE `|safe_html` filter** for dynamic/user content that needs HTML sanitization
- **USE `|raw` filter ONLY** for trusted static translation strings that contain intentional HTML formatting

```twig
{# ❌ DANGEROUS - Never do this for user content #}
{{ user_content|raw }}

{# ✅ SAFE - Use this for user/dynamic content #}
{{ user_content|safe_html }}

{# ✅ SAFE - Use this for trusted translation strings with HTML #}
{{ "static.translation.with.html"|trans|raw }}
{{ "user.provided.content"|trans({'%user_input%': user_data})|safe_html }}
```

**When to use `|raw` vs `|safe_html`:**

- **`|raw`**: Only for static translation strings from `messages.*.yml` that contain trusted HTML (like `<strong>`, `<a href>`, `<br>`)
- **`|safe_html`**: For all dynamic content, user inputs, or when unsure about HTML source safety

The `|safe_html` filter provides:

- Server-side basic sanitization
- Client-side DOMPurify sanitization
- Allows safe HTML tags: `b`, `i`, `em`, `strong`, `u`, `br`, `p`, `span`, `div`, `a`
- Removes dangerous attributes and JavaScript URLs

#### In JavaScript

- **Always sanitize HTML content** before using `innerHTML`:

```javascript
// ❌ DANGEROUS
element.innerHTML = userContent;

// ✅ SAFE
element.innerHTML = sanitizeHTML(userContent);
```

- Use the global `sanitizeHTML()` function for dynamic content
- Content marked with `data-safe-html` is automatically sanitized on page load

### Form Security

- Always use `{{ form_errors(form) }}` and `{{ form_errors(form.field) }}` to display validation errors
- Use CSRF protection for all forms (enabled by default in Symfony)
- Validate all user input on both client and server side

## Template Architecture

### Base Template Hierarchy

The project uses a clean template hierarchy:

- **`base.html.twig`**: Root template with basic HTML structure and global assets
- **`base_page.html.twig`**: For full-page layouts with header, subtitle, and content areas
- **`base_step.html.twig`**: For step-by-step processes (registration, recovery, etc.)

### Template Block Structure

#### Page Templates (extending `base_page.html.twig`)

```twig
{% extends 'base_page.html.twig' %}

{% block title %}{{ domain }} - {{ "page.title"|trans }}{% endblock %}
{% block page_title %}{{ "welcome.heading"|trans }}{% endblock %}
{% block page_subtitle %}{{ "welcome.description"|trans }}{% endblock %}

{% block page_content %}
    {# Your page content here #}
{% endblock %}
```

#### Step Templates (extending `base_step.html.twig`)

```twig
{% extends 'base_step.html.twig' %}

{% block title %}{{ domain }} - {{ "step.title"|trans }}{% endblock %}
{% block step_title %}{{ "registration.heading"|trans }}{% endblock %}
{% block step_description %}{{ "registration.description"|trans }}{% endblock %}

{% block step_content %}
    {# Your step content here #}
{% endblock %}
```

### Title and Subtitle Handling

- **`{% block title %}`**: Browser tab titles (format: `{{ domain }} - {{ "page.title"|trans }}`)
- **`{% block page_title %}`**: Main page headings (H1)
- **`{% block page_subtitle %}`**: Page descriptions (automatically styled by base template)
- **`{% block step_title %}`**: Step process headings
- **`{% block step_description %}`**: Step process descriptions

> **Note**: Subtitle styling is automatically applied - just provide the text content without HTML wrapper.

## Twig Development Guidelines

### HTML Structure

- **Use semantic HTML**: Always use appropriate HTML elements (`<header>`, `<main>`, `<section>`, `<article>`, `<nav>`, `<aside>`, `<footer>`)
- **Ensure accessibility**: Include proper ARIA attributes, alt text for images, and semantic markup
- **Responsive layout**: All layouts must be responsive and work across different device sizes
- **Proper heading hierarchy**: Follow h1 → h2 → h3 structure

### CSS Styling

- **Use Tailwind Utility Classes**: Style components using Tailwind CSS utility classes instead of custom CSS
- **Utility-first approach**: Prefer utility classes for consistent design system
- **Responsive utilities**: Use responsive prefixes (`sm:`, `md:`, `lg:`, `xl:`)
- **Consistent spacing**: Use Tailwind spacing utilities for consistent layouts

### Icon Design Consistency

- **Icon Background Shapes**: Use consistent rounded corners based on icon size:
  - **Small icons** (`w-10 h-10`): Use `rounded-full` for inline header icons
  - **Medium icons** (`w-12 h-12`): Use `rounded-xl` for section headers
  - **Large icons** (`w-16 h-16` and above): Use `rounded-xl` for cards and main features
- **Icon Comments**: Always include Heroicon name comments above SVG icons
- **Color Consistency**: Use semantic color schemes (blue for primary actions, green for success, red for danger, etc.)

### Content and Translations

- **Use Symfony Translations**: Always use `{{ 'key'|trans }}` or `{% trans %}` for all user-facing text
- **No hardcoded strings**: Never include custom strings directly in templates
- **Translation parameters**: Use proper parameter syntax `{{ 'key'|trans({'%param%': value}) }}`
- **Follow naming conventions**: Use consistent translation key patterns

### Error Handling in Templates

Always include proper error display in forms:

```twig
{% form_theme form 'Form/fields.html.twig' %}

{{ form_start(form) }}
    {{ form_errors(form) }}

    {{ form_row(form.field) }}
    {{ form_errors(form.field) }}
{{ form_end(form) }}
```

### Modern Layout Patterns

- **Avoid nested card layouts**: Use single-level cards for cleaner design
- **Use step-like layouts**: For multi-step processes
- **Implement responsive grids**: With Tailwind's grid system
- **Single responsibility**: Each template block should have one clear purpose

### Template Reuse Guidelines

#### When to Share Templates Between Actions

**✅ RECOMMENDED:**

- **Form handling**: GET (show form) and POST (process form) for the same entity
- **CRUD operations**: Create and edit forms with similar structure
- **Different output formats**: Same content in different presentations (HTML, print, PDF)

```twig
{# ✅ GOOD: Shared form template #}
{# user/form.html.twig #}
{% extends 'base_page.html.twig' %}

{% block page_title %}
    {{ mode == 'create' ? 'user.create.title'|trans : 'user.edit.title'|trans }}
{% endblock %}

{% block page_content %}
    {{ form_start(form) }}
        {{ form_row(form.email) }}
        {% if mode == 'create' %}
            {{ form_row(form.password) }}
        {% endif %}
        {{ form_row(form.name) }}

        <button type="submit" class="btn btn-primary">
            {{ mode == 'create' ? 'user.create.submit'|trans : 'user.update.submit'|trans }}
        </button>
    {{ form_end(form) }}
{% endblock %}
```

**❌ AVOID:**

- **Different business contexts**: Admin vs user dashboards
- **Complex conditional logic**: Multiple unrelated features in one template
- **Different security levels**: Public vs authenticated content

```twig
{# ❌ BAD: Too much conditional logic #}
{% if user_type == 'admin' %}
    {% include 'admin/_dashboard.html.twig' %}
{% elseif user_type == 'moderator' %}
    {% include 'moderator/_dashboard.html.twig' %}
{% else %}
    {% include 'user/_dashboard.html.twig' %}
{% endif %}
```

#### Alternative Approaches

**Prefer Template Inheritance:**

```twig
{# base_user_form.html.twig #}
{% extends 'base_page.html.twig' %}

{% block page_content %}
    {{ form_start(form) }}
        {% block user_form_fields %}
            {# Override in child templates #}
        {% endblock %}

        {% block user_form_actions %}
            <button type="submit" class="btn btn-primary">{{ submit_label }}</button>
        {% endblock %}
    {{ form_end(form) }}
{% endblock %}
```

**Use Template Composition:**

```twig
{# user/create.html.twig #}
{% extends 'base_page.html.twig' %}

{% block page_content %}
    {% include 'user/_form_header.html.twig' with {'mode': 'create'} %}
    {% include 'user/_form_fields.html.twig' %}
    {% include 'user/_form_actions.html.twig' with {'action': 'create'} %}
{% endblock %}
```

## JavaScript Guidelines

### Security

- **Always sanitize dynamic HTML**: Use `sanitizeHTML()` before setting `innerHTML`
- **Validate user input**: Both client and server side
- **Avoid inline event handlers**: Use proper event delegation

### Code Organization

- **Use modern JavaScript**: ES6+ features are supported
- **Event delegation**: Prefer event delegation for dynamic content
- **Modular functions**: Keep functions small and focused
- **Clear naming**: Use descriptive function and variable names

### DOMPurify Integration

```javascript
// For dynamic content
element.innerHTML = sanitizeHTML(userContent);

// Automatic processing for marked elements
<div data-safe-html>{{ content }}</div>;
```

## Symfony Development Guidelines

### PSR Standards Compliance

#### PSR-1: Basic Coding Standard

- **File Naming**: Use `StudlyCaps` for class names
- **Method Naming**: Use `camelCase` for method and property names
- **Constant Naming**: Use `UPPER_CASE` with underscores for class constants
- **Namespace Declaration**: Always declare namespaces and use statements

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class UserController extends AbstractController
{
    private const MAX_USERS_PER_PAGE = 50;

    public function listUsers(): Response
    {
        // Implementation
    }
}
```

#### PSR-4: Autoloading Standard

- **Namespace Structure**: Follow `App\` namespace for `src/` directory
- **Directory Structure**: Match namespace structure with directory structure
- **File Organization**: One class per file, matching class name

```php
// ✅ CORRECT: src/Controller/User/ProfileController.php
namespace App\Controller\User;

// ✅ CORRECT: src/Service/Email/NotificationService.php
namespace App\Service\Email;
```

#### PSR-12: Extended Coding Style

- **Indentation**: Use 4 spaces, no tabs
- **Line Length**: Keep lines under 120 characters
- **Braces**: Opening braces on same line for control structures, new line for classes/methods
- **Import Statements**: Group and alphabetize use statements

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    public function createUser(array $userData): User
    {
        if (empty($userData['email'])) {
            throw new \InvalidArgumentException('Email is required');
        }

        // Implementation
    }
}
```

### Object-Oriented Programming Principles

#### SOLID Principles

##### Single Responsibility Principle (SRP)

Each class should have one reason to change:

```php
// ❌ BAD: Multiple responsibilities
class UserManager
{
    public function createUser(array $data): User { }
    public function sendWelcomeEmail(User $user): void { }
    public function validateUserData(array $data): bool { }
    public function logUserAction(User $user, string $action): void { }
}

// ✅ GOOD: Separated responsibilities
class UserCreator
{
    public function createUser(array $data): User { }
}

class WelcomeEmailSender
{
    public function sendWelcomeEmail(User $user): void { }
}

class UserValidator
{
    public function validateUserData(array $data): bool { }
}
```

##### Open/Closed Principle (OCP)

Classes should be open for extension, closed for modification:

```php
// ✅ GOOD: Using interfaces for extensibility
interface NotificationSenderInterface
{
    public function send(string $message, string $recipient): void;
}

class EmailNotificationSender implements NotificationSenderInterface
{
    public function send(string $message, string $recipient): void
    {
        // Email implementation
    }
}

class SmsNotificationSender implements NotificationSenderInterface
{
    public function send(string $message, string $recipient): void
    {
        // SMS implementation
    }
}

class NotificationService
{
    public function __construct(
        private readonly NotificationSenderInterface $sender
    ) {
    }
}
```

##### Liskov Substitution Principle (LSP)

Derived classes must be substitutable for their base classes:

```php
// ✅ GOOD: Proper inheritance
abstract class AbstractUser
{
    abstract public function getPermissions(): array;
}

class RegularUser extends AbstractUser
{
    public function getPermissions(): array
    {
        return ['read', 'write'];
    }
}

class AdminUser extends AbstractUser
{
    public function getPermissions(): array
    {
        return ['read', 'write', 'admin', 'delete'];
    }
}
```

##### Interface Segregation Principle (ISP)

Many specific interfaces are better than one general-purpose interface:

```php
// ❌ BAD: Fat interface
interface UserManagementInterface
{
    public function createUser(array $data): User;
    public function deleteUser(int $id): void;
    public function sendEmail(User $user, string $message): void;
    public function generateReport(): string;
}

// ✅ GOOD: Segregated interfaces
interface UserCreatorInterface
{
    public function createUser(array $data): User;
}

interface UserRemoverInterface
{
    public function deleteUser(int $id): void;
}

interface EmailSenderInterface
{
    public function sendEmail(User $user, string $message): void;
}
```

##### Dependency Inversion Principle (DIP)

Depend on abstractions, not concretions:

```php
// ✅ GOOD: Depending on interfaces
class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EmailSenderInterface $emailSender,
        private readonly LoggerInterface $logger
    ) {
    }
}
```

### Design Patterns Implementation

#### Repository Pattern

```php
// Entity
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;
}

// Repository Interface
interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;
    public function findActiveUsers(): array;
    public function save(User $user): void;
}

// Repository Implementation
class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findActiveUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
```

#### Factory Pattern

```php
interface UserFactoryInterface
{
    public function createUser(string $email, string $type): User;
}

class UserFactory implements UserFactoryInterface
{
    public function createUser(string $email, string $type): User
    {
        return match ($type) {
            'admin' => new AdminUser($email),
            'regular' => new RegularUser($email),
            default => throw new \InvalidArgumentException("Invalid user type: {$type}")
        };
    }
}
```

#### Command Pattern (CQRS)

```php
// Command
class CreateUserCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly array $roles = []
    ) {
    }
}

// Command Handler
class CreateUserCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function __invoke(CreateUserCommand $command): User
    {
        $user = new User();
        $user->setEmail($command->email);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $command->password)
        );
        $user->setRoles($command->roles);

        $violations = $this->validator->validate($user);
        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        $this->userRepository->save($user);

        return $user;
    }
}
```

### Symfony Best Practices

#### Controller Guidelines

- **Keep controllers thin**: Move business logic to services
- **One HTTP method per action**: Each controller method should handle only one HTTP method for better SRP compliance
- **Use dependency injection**: Constructor injection for services
- **Return appropriate responses**: Use proper HTTP status codes
- **Handle exceptions properly**: Use exception listeners
- **REST-compliant naming**: Use clear, RESTful action names (list, show, create, update, delete)

```php
#[Route('/api/users', methods: ['POST'])]
class CreateUserController extends AbstractController
{
    public function __construct(
        private readonly CreateUserCommandHandler $commandHandler
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $command = new CreateUserCommand(
                email: $data['email'] ?? '',
                password: $data['password'] ?? '',
                roles: $data['roles'] ?? []
            );

            $user = $this->commandHandler->__invoke($command);

            return $this->json([
                'id' => $user->getId(),
                'email' => $user->getEmail()
            ], Response::HTTP_CREATED);

        } catch (ValidationException $e) {
            return $this->json([
                'error' => 'Validation failed',
                'violations' => $e->getViolations()
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
```

#### Service Layer Architecture

```php
// Service Interface
interface UserServiceInterface
{
    public function createUser(CreateUserCommand $command): User;
    public function getUserById(int $id): User;
    public function updateUser(int $id, UpdateUserCommand $command): User;
}

// Service Implementation
class UserService implements UserServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function createUser(CreateUserCommand $command): User
    {
        $user = User::create($command->email, $command->password);

        $violations = $this->validator->validate($user);
        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        $this->userRepository->save($user);

        $this->eventDispatcher->dispatch(
            new UserCreatedEvent($user),
            UserCreatedEvent::NAME
        );

        return $user;
    }
}
```

#### Event-Driven Architecture

```php
// Event
class UserCreatedEvent
{
    public const NAME = 'user.created';

    public function __construct(
        private readonly User $user
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }
}

// Event Subscriber
class UserEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EmailSenderInterface $emailSender
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserCreatedEvent::NAME => 'onUserCreated',
        ];
    }

    public function onUserCreated(UserCreatedEvent $event): void
    {
        $this->emailSender->sendWelcomeEmail($event->getUser());
    }
}
```

### Code Quality and Complexity Management

#### Cyclomatic Complexity

Keep methods simple with low complexity (max 10):

```php
// ❌ BAD: High complexity
public function processUser(User $user): string
{
    if ($user->isActive()) {
        if ($user->hasRole('ADMIN')) {
            if ($user->getLastLogin() > new \DateTime('-30 days')) {
                if ($user->hasCompletedProfile()) {
                    return 'active_admin_recent_complete';
                } else {
                    return 'active_admin_recent_incomplete';
                }
            } else {
                return 'active_admin_old';
            }
        } else {
            return 'active_user';
        }
    } else {
        return 'inactive';
    }
}

// ✅ GOOD: Lower complexity using early returns and extraction
public function processUser(User $user): string
{
    if (!$user->isActive()) {
        return 'inactive';
    }

    if (!$user->hasRole('ADMIN')) {
        return 'active_user';
    }

    return $this->processAdminUser($user);
}

private function processAdminUser(User $user): string
{
    if ($user->getLastLogin() <= new \DateTime('-30 days')) {
        return 'active_admin_old';
    }

    return $user->hasCompletedProfile()
        ? 'active_admin_recent_complete'
        : 'active_admin_recent_incomplete';
}
```

#### Error Handling

```php
// Custom Exception Hierarchy
abstract class UserException extends \Exception
{
}

class UserNotFoundException extends UserException
{
}

class UserValidationException extends UserException
{
    public function __construct(
        private readonly ConstraintViolationListInterface $violations
    ) {
        parent::__construct('User validation failed');
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }
}

// Service with proper error handling
class UserService
{
    public function getUserById(int $id): User
    {
        $user = $this->userRepository->find($id);

        if ($user === null) {
            throw new UserNotFoundException("User with ID {$id} not found");
        }

        return $user;
    }
}
```

#### Type Safety

```php
// ✅ Use strict typing
declare(strict_types=1);

// ✅ Use typed properties
class User
{
    private string $email;
    private ?string $name = null;
    private array $roles = [];
    private \DateTimeInterface $createdAt;
}

// ✅ Use return type declarations
public function findUsersByRole(string $role): array
{
    return $this->userRepository->findByRole($role);
}

// ✅ Use parameter type hints
public function updateUser(User $user, UpdateUserCommand $command): void
{
    // Implementation
}
```

#### Performance Considerations

```php
// ✅ Efficient database queries
class UserRepository extends ServiceEntityRepository
{
    public function findUsersWithRoles(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u', 'r')
            ->leftJoin('u.roles', 'r')
            ->getQuery()
            ->getResult();
    }

    public function findActiveUsersCount(): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

// ✅ Use pagination for large datasets
public function getUsersPaginated(int $page, int $limit): PaginationInterface
{
    $query = $this->userRepository->createQueryBuilder('u')
        ->getQuery();

    return $this->paginator->paginate($query, $page, $limit);
}
```

### Testing Guidelines

#### Unit Testing

```php
class UserServiceTest extends TestCase
{
    private UserService $userService;
    private MockObject $userRepository;
    private MockObject $eventDispatcher;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->userService = new UserService(
            $this->userRepository,
            $this->eventDispatcher,
            $this->createMock(ValidatorInterface::class)
        );
    }

    public function testCreateUserSuccessfully(): void
    {
        $command = new CreateUserCommand('test@example.com', 'password');

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(User::class));

        $user = $this->userService->createUser($command);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test@example.com', $user->getEmail());
    }
}
```

#### Integration Testing

```php
class UserControllerIntegrationTest extends WebTestCase
{
    public function testCreateUser(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'secure_password'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertJson($client->getResponse()->getContent());
    }
}
```

#### Pragmatic Refactoring Approach

**Balance guidelines with practicality - avoid over-engineering:**

##### When to Split Controllers

**✅ SPLIT when:**

- Controllers handle completely different business domains (User vs Order management)
- Actions have different security requirements (public vs admin-only)
- Controllers become genuinely large (100+ lines) with unrelated functionality

**❌ DON'T SPLIT when:**

- The functionality is tightly coupled (form display + form processing)
- It would create more complexity than value
- Tests and templates would require significant changes for minimal benefit

##### Form Handling Pattern (Recommended)

For traditional web forms, use this pragmatic approach:

```php
class AliasController extends AbstractController
{
    #[Route('/alias', methods: ['GET'])]
    public function show(): Response
    {
        // Display alias overview with forms
        $form = $this->createForm(AliasType::class, new Alias(), [
            'action' => $this->generateUrl('alias_create'),
            'method' => 'post',
        ]);

        return $this->render('alias/show.html.twig', [
            'form' => $form->createView(),
            // ...other data
        ]);
    }

    #[Route('/alias/create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        // Process form submission
        $form = $this->createForm(AliasType::class, new Alias());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Process the form
            $this->addFlash('success', 'Created successfully');
        }

        return $this->redirectToRoute('alias_show');
    }
}
```

##### Benefits of This Approach

- **One HTTP method per action** ✅ (follows SRP)
- **Minimal template changes** ✅ (forms point to different routes)
- **Easy testing** ✅ (GET and POST can be tested separately)
- **Maintainable** ✅ (related functionality stays together)
- **Not over-engineered** ✅ (realistic for simple CRUD operations)

##### Naming Conventions

- **`show()`**: Display data with possible forms (not just "list")
- **`create()`**: Process form submissions for creation
- **`edit()`**: Display edit form
- **`update()`**: Process edit form submissions
- **`delete()`**: Handle deletion

##### Migration Strategy

When refactoring existing controllers:

1. **Start small**: Split only the most obvious violations first
2. **Test impact**: Consider effects on existing tests and templates
3. **Incremental improvement**: Make controllers better without rewriting everything
4. **Consistent patterns**: Follow existing codebase conventions

**Remember: Perfect is the enemy of good. Focus on meaningful improvements that enhance maintainability without creating unnecessary complexity.**
