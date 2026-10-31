# Keycloak

With the [Keycloak User Provider for Userli](https://github.com/systemli/userli-keycloak-provider),
[Keycloak](https://www.keycloak.org/) uses Userli accounts for single sign-on.
Users can then sign in to other services (for example Nextcloud, Matrix or a
wiki) with their email address and password – and their Userli second factor:
authenticator app, security key or backup code.

The provider uses the `/api/keycloak/` endpoints to search users, count them
and validate passwords, TOTP codes, backup codes and security keys. Keycloak
doesn't store passwords or keys: users manage their factors in Userli only.

## On the Userli host

Create an API token with the scope `keycloak`:

```shell
bin/console app:api-token:create --name "Keycloak" --scopes keycloak
```

## In Keycloak

Install the provider and add it as a user federation provider in your realm.
Configure the Userli URL, the domain and the API token. See the
[provider README](https://github.com/systemli/userli-keycloak-provider) and
Keycloak's documentation on
[user storage providers](https://www.keycloak.org/docs/latest/server_development/#_user-storage).

## Two-factor authentication

The provider adds two authenticators for Keycloak's browser flow:

* **Userli Security Key**: Userli creates a single-use challenge, Keycloak
  shows its WebAuthn login page with it and Userli verifies the response
  against the stored key.
* **Userli Backup Code**: accepts the backup codes from Userli, also for users
  with only security keys. The **OTP Form** accepts backup codes as well.

Add both as *Alternative* next to **OTP Form** in the conditional 2FA sub-flow
(**Condition - user configured**) of your browser flow. Users switch between
their factors with *Try another way*.

Security keys are bound to a domain, so Userli and Keycloak need a common
parent domain, for example `users.example.org` and `login.example.org`:

1. In Userli, set **Security Key Domain** to `example.org` and add the Keycloak
   origin, e.g. `https://login.example.org`, to
   **Additional Security Key Origins** (see [Settings](../../admin/settings.md#two-factor-authentication)).
2. In Keycloak, set **Authentication → Policies → WebAuthn Policy → Relying
   Party ID** to the same domain.

The authenticators need version 1.1 of the provider or later. See the
[provider README](https://github.com/systemli/userli-keycloak-provider) for
details.

## Account status

Credential validation enforces the account status like the Dovecot API:

| State                           | HTTP | `message`                  |
|---------------------------------|-----:|----------------------------|
| Authenticated                   |  200 | `success`                  |
| Unknown user or bad credentials |  403 | `authentication failed`    |
| Deleted or role *Spam*          |  403 | `authentication failed`    |
| Password change required        |  403 | `password change required` |
| Factor requested but not set up |  403 | `not supported`            |
| Unknown credential type         |  400 | `not supported`            |

Each successful password validation updates the last login time of the user,
so active Keycloak users are not removed as inactive.

All endpoints are listed in the [API reference](../api.md#keycloak).
