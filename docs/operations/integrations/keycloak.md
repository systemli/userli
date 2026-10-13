# Keycloak

With the [Keycloak User Provider for Userli](https://github.com/systemli/userli-keycloak-provider),
[Keycloak](https://www.keycloak.org/) uses Userli accounts for single sign-on.
Users can then sign in to other services (for example Nextcloud, Matrix or a
wiki) with their email address and password – and their Userli 2FA code.

The provider uses the `/api/keycloak/` endpoints to search users, count them
and validate passwords and TOTP codes. Keycloak doesn't store passwords.

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

## Account status

Credential validation enforces the account status like the Dovecot API:

| State                           | HTTP | `message`                  |
|---------------------------------|-----:|----------------------------|
| Authenticated                   |  200 | `success`                  |
| Unknown user or bad credentials |  403 | `authentication failed`    |
| Deleted or role *Spam*          |  403 | `authentication failed`    |
| Password change required        |  403 | `password change required` |
| OTP requested but not enrolled  |  403 | `not supported`            |
| Unknown credential type         |  400 | `not supported`            |

Each successful validation updates the last login time of the user, so active
Keycloak users are not removed as inactive.

All endpoints are listed in the [API reference](../api.md#keycloak).
