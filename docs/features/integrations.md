# Integrations

Userli provides several authenticated APIs to integrate with external systems (Keycloak, Dovecot, Postfix, Retention, Roundcube).
Access is now controlled via API Tokens with fine-grained scopes. The previous env-var based access tokens and IP allowlists are no longer used.

## API tokens and scopes

Create and manage tokens via UI: Settings → API Tokens (`/settings/api`).

CLI:

- Create: `bin/console app:api-token:create --name "My Token" --scopes keycloak --scopes dovecot`
- Delete: `bin/console app:api-token:delete --token <PLAIN_TOKEN>`

Scopes control which API areas the token can call:

| Scope       | Endpoint prefix       | Description                                              |
|-------------|-----------------------|----------------------------------------------------------|
| `keycloak`  | `/api/keycloak/`      | Keycloak user lookup, count, and validation              |
| `dovecot`   | `/api/dovecot/`       | Dovecot passdb auth and lookup                           |
| `postfix`   | `/api/postfix/`       | Postfix helpers (domain/mailbox/alias/senders)           |
| `retention` | `/api/retention/`     | Update last login and list deleted users                 |
| `roundcube` | `/api/roundcube/`     | Roundcube helpers (e.g., list user aliases after auth)   | 

!!! tip "How to send the token"
    Preferred header:

    - `Authorization: Bearer <PLAIN_TOKEN>` ("Bearer" is case-insensitive)

    Fallback header:

    - `X-API-Token: <PLAIN_TOKEN>` (used only if no valid Bearer token is present)

    Bearer takes precedence over `X-API-Token`. An invalid Authorization format falls back to `X-API-Token` when present.

!!! note "Token visibility and tracking"

    - Tokens are stored hashed; the plain token is shown only once on creation. Copy and store it safely.
    - Last-used timestamps are tracked and shown in the UI.
    - Endpoints enforce required scopes automatically; missing scopes result in HTTP 403.

## Keycloak

Userli exposes `/api/keycloak/` endpoints for user lookup and verification, intended for use with a Keycloak user storage provider.

See the [Keycloak User Provider for Userli](https://github.com/systemli/userli-keycloak-provider) and Keycloak's official docs on [user storage providers](https://www.keycloak.org/docs/latest/server_development/#_user-storage) for reference.

### Examples

Search users:

    GET /api/keycloak/<domain>/?search=john&max=10&first=0
    Authorization: Bearer <PLAIN_TOKEN_WITH_keycloak_SCOPE>


## Dovecot

Userli exposes `/api/dovecot/` endpoints for Dovecot userdb and passdb lookups, intended to be consumed by the [Lua adapter script](https://github.com/systemli/userli/blob/main/contrib/userli-dovecot-adapter.lua).

See the step-by-step setup guide at [Installation › Dovecot](../installation/dovecot.md) and Dovecot' official docs on [Lua-based authentication](https://doc.dovecot.org/latest/core/config/auth/databases/lua.html#lua-authentication-database-lua).

## Roundcube

Userli exposes `/api/roundcube/` endpoints to assist Roundcube in user alias lookups after authentication, intended to be consumed by the [Roundcube Userli Plugin](https://packagist.org/packages/systemli/userli).

## Postfix

User exposes `/api/postfix/` endpoints for Postfix integration, intended to be consumed by the [Postfix Userli Adapter](https://github.com/systemli/userli-postfix-adapter).

## Retention

Userli provides generic API methods at `/api/retention/` to:

- Touch a user’s last login timestamp independent of authentication
- List deleted users for a domain

Background: Each time a user authenticates (classic login, or via Keycloak/Dovecot APIs), Userli updates the user's last login time.
Some services issue long-lived tokens and won’t re-authenticate regularly, which can make last-login based retention inaccurate
and complicate invalidation of stale client tokens.

See [userli-synapse-user-retention](https://github.com/systemli/userli-synapse-user-retention) for an example client implementation for Matrix Synapse.

### Examples

Touch last login (optional unix timestamp, must not be in the future):

    PUT /api/retention/<email>/touch
    Authorization: Bearer <PLAIN_TOKEN_WITH_retention_SCOPE>
    Content-Type: application/json

    { "timestamp": 1693843200 }

List deleted users for a domain:

    GET /api/retention/<domain>/users
    Authorization: Bearer <PLAIN_TOKEN_WITH_retention_SCOPE>

## Migration notes (deprecated settings)

!!! warning "Replaced by scoped API tokens"

    The following options are deprecated and have been removed in favor of scoped API tokens:

    - `KEYCLOAK_API_ENABLED`, `KEYCLOAK_API_IP_ALLOWLIST`, `KEYCLOAK_API_ACCESS_TOKEN`
    - `RETENTION_API_ENABLED`, `RETENTION_API_IP_ALLOWLIST`, `RETENTION_API_ACCESS_TOKEN`

    Use a scoped API token instead and pass it via `Authorization: Bearer ...` (or `X-API-Token`).
