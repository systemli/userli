# Keycloak

With the [Keycloak User Provider for Userli](https://github.com/systemli/userli-keycloak-provider),
[Keycloak](https://www.keycloak.org/) uses Userli accounts for single sign-on.
Users can then sign in to other services (for example Nextcloud, Matrix or a
wiki) with their email address and password – and their Userli second factor:
authenticator app, security key or backup code.

The provider uses the `/api/keycloak/` endpoints to search users, count them,
validate passwords, TOTP codes and backup codes, and to read the public keys of
security keys. Keycloak doesn't store passwords or keys: users manage their
factors in Userli only.

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

Keycloak checks security keys with its own **WebAuthn Authenticator**. The
provider hands it the keys of the user from Userli, so Keycloak verifies the
assertion against the stored public key and reports the new sign count back to
Userli. For backup codes, the provider adds the authenticator
**Userli Backup Code**, which also works for users with only security keys.
The **OTP Form** accepts backup codes as well.

Add **WebAuthn Authenticator** and **Userli Backup Code** as *Alternative* next
to **OTP Form** in the conditional 2FA sub-flow (**Condition - user
configured**) of your browser flow. Users switch between their factors with
*Try another way*. Keep the required action **Webauthn Register** disabled,
security keys are registered in Userli.

Security keys are bound to a domain, so Userli and Keycloak need a common
parent domain, for example `users.example.org` and `login.example.org`:

1. In Userli, set **Security Key Domain** to `example.org` (see
   [Settings](../../admin/settings.md#two-factor-authentication)).
2. In Keycloak, set **Authentication → Policies → WebAuthn Policy → Relying
   Party ID** to the same domain.

Keycloak checks the origin of the login page itself, so its origin doesn't need
to be in **Additional Security Key Origins**.

Set the rest of the **WebAuthn Policy** to match Userli:

| Option | Value | Why |
|--------|-------|-----|
| Signature algorithms | `ES256`, `EdDSA`, `RS256` | Userli accepts these three when a key is registered. Without `EdDSA` (Keycloak's default is `ES256` and `RS256` only), keys that use Ed25519 may fail in Keycloak. |
| User verification requirement | `discouraged` or `not specified` | The security key is the second factor after the password, so Userli asks for touch only, not for a PIN or fingerprint. With `required`, keys without a PIN can't be used. |

!!! note "Keycloak version"

    Keycloak supports Ed25519 since version 24. Use version 24 or later, else
    users with an Ed25519 key can only use backup codes in Keycloak.

See the [provider README](https://github.com/systemli/userli-keycloak-provider)
for details.

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
