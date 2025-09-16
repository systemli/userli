# AI Contribution Guidelines

Welcome, 🤖 AI assistant! Please follow these guidelines when contributing to Userli:

## Project Overview

- This is Userli: Manage Email Users with variouis integrations
- Userli is built on top of **Symfony**
- Backend using Symfony, Doctrine, Twig and Monolog
- Frontend using Symfony UX, TailwindCSS and Heroicons
- Testing using PHPUnit and Behat
- Development environment using Podman and Docker (prefer Podman)

## General Guidelines

### Project Structure & Design

- `src/` - Application code (Controllers, Entities, Repositories, Services, etc.)
- `templates/` - Twig templates for rendering views
- `config/` - Configuration files (services, routes, packages, etc.)
- `public/` - Publicly accessible files (entry point, assets, etc.)
- `tests/` - Unit tests
- `docs/` - Documentation files (mkdocs with Material theme)
- `Dockerfile` & `docker-compose.yml` - Containerization and development environment setup

### Compatibility & Security

- Ensure compatibility with **Symfony** and **PHP** versions defined in `composer.json`
- Follow secure coding practices to prevent XSS, CSRF, injections, auth bypasses, etc.

### Coding Standards & Tooling

- Follow Symfony coding standards  with `@Symfony` PHP CS Fixer rules
- Use **4 spaces** for indentation in all files (PHP, YAML, XML, Twig, etc.)
- Always add newlines at end of files

### Testing Guidelines

- Use **PHPUnit** for unit and functional testing
- Use **Behat** for behavior-driven scenarios
- Prefer `MockHttpClient` over response mocking
- Use `self::assert*` or `$this->assert*` in tests
- No void return types for test methods
- Always fix risky tests

### Commands

- Run `composer install --no-scripts` to install PHP dependencies
- Run `bin/console doctrine:fixture
- Run `yarn install` to install JavaScript dependencies
- Run `yarn encore dev` to compile frontend assets
- Run `podman compose up -d` to start the development environment
- Use `podman compose exec -it userli <command>` as command prefix when you want to interact with the development environment
- Run `bin/behat --format progress` to run behavior-driven scenarios
- Run `bin/console doctrine:fixtures:load --group=basic --env=test -n` to load basic fixtures for testing
- Run `bin/phpunit` to run unit and functional tests (requires basic fixtures)
