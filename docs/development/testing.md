# Testing

## PHPUnit

Unit tests mirror the `src/` directory structure under `tests/`.
Tests use SQLite (configured in `.env.test`), so no running database container is needed.

=== "direct"

    ```shell
    bin/phpunit
    ```

=== "make"

    ```shell
    make test
    ```

## Behat

Behat feature files are in `features/`.
They test user-facing functionality through a browser simulation.

=== "direct"

    ```shell
    bin/behat --format progress
    ```

=== "make"

    ```shell
    make integration
    ```

## Dovecot integration

Dovecot authenticates users and looks up mailbox info via Userli's HTTP API (`/api/dovecot/`).
A Lua adapter (`contrib/userli-dovecot-adapter.lua`) sends requests with a Bearer token to Userli's `DovecotController`.

### Behat tests

The Dovecot API is tested automatically as part of the Behat suite in `features/api_dovecot.feature`.
These tests run in CI without a real Dovecot instance -- they simulate the HTTP requests that Dovecot would make.

The scenarios cover:

- **Status** -- API health check with valid and invalid tokens
- **Passdb** -- password verification for valid users, wrong passwords, nonexistent users, and blocked (spam) users
- **Userdb** -- mailbox lookup for existing users, nonexistent users, and spam users

To run only the Dovecot-related tests:

```shell
bin/behat --tags=@dovecot --format progress
```

### Mailcrypt integration test

A dedicated GitHub Actions workflow (`.github/workflows/mailcrypt.yml`) tests the Dovecot mailcrypt integration end-to-end.
It uses a separate compose file (`docker-compose.mailcrypt-test.yml`) that starts a real Dovecot instance with a Python mock of the Userli API (`tests/dovecot-api-mock.py`).

The test (`tests/mailcrypt_integration.sh`) uploads emails via IMAP to two users:

- A user **with** mailcrypt enabled -- verifies that the email content is **not** readable on disk (encrypted)
- A user **without** mailcrypt -- verifies that the email content **is** readable on disk (plaintext)

This ensures that the Lua adapter, Dovecot's `mail_crypt` plugin, and the API contract work correctly together.

To run the mailcrypt tests locally:

=== "podman"

    ```shell
    COMPOSE_FILE=docker-compose.mailcrypt-test.yml podman compose up -d
    bash tests/mailcrypt_integration.sh
    COMPOSE_FILE=docker-compose.mailcrypt-test.yml podman compose down -v
    ```

=== "docker"

    ```shell
    COMPOSE_FILE=docker-compose.mailcrypt-test.yml docker compose up -d
    bash tests/mailcrypt_integration.sh
    COMPOSE_FILE=docker-compose.mailcrypt-test.yml docker compose down -v
    ```

### Manual testing with containers

The development containers are pre-configured to connect Dovecot to Userli's API.
After loading the fixtures (see [Getting started](index.md)), you can test authentication from inside the Dovecot container:

=== "podman"

    ```shell
    podman compose exec dovecot doveadm auth test user@example.org password
    ```

=== "docker"

    ```shell
    docker compose exec dovecot doveadm auth test user@example.org password
    ```

You can also test the full flow by connecting an IMAP client to `localhost:1143` or by using Roundcube at [http://localhost:8001](http://localhost:8001).

See the Dovecot [documentation](https://doc.dovecot.org/2.3/admin_manual/debugging/debugging_authentication/) for more context.
