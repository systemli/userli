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
