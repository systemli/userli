# Integrations

Although Userli is primarily meant to be used as a backend and user self-service for a Mail service such as Dovecot,
it provices a few optional APIs for integration with other services:

## Keycloak

Userli provides an API at `/api/keycloak/` for user lookup and verification by a custom Keycloak Provider.
See the [KeyCloak User Provider for Userli](https://github.com/systemli/userli-keycloak-provider) for reference.

Following env vars need to be set:
```shell
KEYCLOAK_API_ENABLED=true
# Access is restricted to these IPs (supports subnets like `10.0.0.1/24`)
KEYCLOAK_API_IP_ALLOWLIST="127.0.0.1, ::1"
KEYCLOAK_API_ACCESS_TOKEN="replace-me-with-a-secure-token"
```

## Retention

Each time a user is authenticated - regardless if via classic login or via the Keycloak or Dovecot APIs - the last login
time of the user is updated.

Some services do not re-authenticate clients on every use, but rather generate long-lived token once, which can cause 
problems: 
If one wants to delete users after a certain limit, the timestamp of the  last login might not reflect the actual last
usage. 
A service also might be unable to tell if a user actually still exists and thus not know when to invalidate its client
tokens.

Userli provides some generic API methods at `/api/retention/` to update the last login time of a user independent of
the authentication process and to get a list of deleted users for a domain.

See [this project](https://github.com/systemli/userli-synapse-user-retention) for an example implementation.

Following env vars need to be set to enable the API:
```shell
RETENTION_API_ENABLED=true
# Access is restricted to these IPs (supports subnets like `10.0.0.1/24`)
RETENTION_API_IP_ALLOWLIST="127.0.0.1, ::1"
RETENTION_API_ACCESS_TOKEN="replace-me-with-a-secure-token"
```