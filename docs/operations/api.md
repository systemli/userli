# API reference

All API endpoints require an [API token](integrations/index.md#api-tokens)
with the matching scope:

```text
Authorization: Bearer <PLAIN_TOKEN>
```

Responses are JSON. A missing or invalid token results in `401`, a token
without the required scope in `403`.

## Dovecot

Scope `dovecot`. Used by the [Dovecot Lua adapter](integrations/dovecot.md).

| Method | Path | Description |
|--------|------|-------------|
| `GET`  | `/api/dovecot/status` | Health check. Returns `{"message": "success"}`. |
| `GET`  | `/api/dovecot/{email}` | userdb lookup. |
| `POST` | `/api/dovecot/{email}` | passdb lookup: verify the password. |

**userdb lookup** response:

```json
{
  "message": "success",
  "body": {
    "user": "user@example.org",
    "mailCrypt": 2,
    "mailCryptPublicKey": "<base64>",
    "quota": "1024M"
  }
}
```

`mailCrypt` is `2` if MailCrypt is enabled for the user, otherwise `0`.
`quota` is empty if no quota is set. Unknown and deleted users return `404`.

**passdb lookup** request and response:

```json
{ "password": "<password>" }
```

```json
{
  "message": "success",
  "body": {
    "mailCrypt": 2,
    "mailCryptPrivateKey": "<base64>",
    "mailCryptPublicKey": "<base64>"
  }
}
```

| Status | Meaning |
|-------:|---------|
| `200`  | Authenticated |
| `401`  | Wrong password |
| `403`  | Role *Spam* or password change required |
| `404`  | Unknown or deleted user |
| `500`  | MailCrypt key could not be decrypted |

## Postfix

Scope `postfix`. Used by the [Postfix adapter](integrations/postfix.md).

| Method | Path | Response |
|--------|------|----------|
| `GET`  | `/api/postfix/domain/{domain}` | `true` if the domain is managed by Userli |
| `GET`  | `/api/postfix/mailbox/{email}` | `true` if an active user with this address exists |
| `GET`  | `/api/postfix/alias/{email}` | List of destinations of the alias, e.g. `["user@example.org"]` |
| `GET`  | `/api/postfix/senders/{email}` | List of users allowed to send with this address |
| `GET`  | `/api/postfix/smtp_quota/{email}` | `{"per_hour": 100, "per_day": 1000}`, `0` = unlimited; `404` for unknown users |
| `POST` | `/api/postfix/auth` | SMTP authentication, body `{"email": "...", "password": "..."}` |

**auth** status codes: `200` success, `401` authentication failed, `403`
role *Spam* or password change required.

## Roundcube

Scope `roundcube`. Used by the [Roundcube plugin](integrations/roundcube.md).

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/api/roundcube/aliases` | Body `{"email": "...", "password": "..."}`. Returns the list of the user's alias addresses. `401` on wrong credentials. App passwords are rejected. |
| `POST` | `/api/roundcube/login` | Body `{"email": "...", "password": "...", "code": "..."}`. Checks the main password and, if the user has two-factor authentication, the TOTP or backup code in `code`. Returns `{"password": "...", "expiresAt": "<ISO 8601>"}`, a webmail credential that is valid for 12 hours in Dovecot and Postfix. `401` with message `twofactor required` if `code` is missing, `authentication failed` otherwise. `403` for users with the spam role or a required password change. `429` after 10 attempts per user within 15 minutes. App passwords are rejected. |
| `POST` | `/api/roundcube/logout` | Body `{"email": "...", "password": "<webmail credential>"}`. Revokes the webmail credential. Always returns `204`. |

## Keycloak

Scope `keycloak`. Used by the [Keycloak user provider](integrations/keycloak.md).

| Method | Path | Description |
|--------|------|-------------|
| `GET`  | `/api/keycloak/{domain}?search=&max=10&first=0` | Search users. Returns `[{"id": "<local part>", "email": "..."}]`. |
| `GET`  | `/api/keycloak/{domain}/count` | Number of users in the domain. |
| `GET`  | `/api/keycloak/{domain}/user/{email}` | Get a single user. |
| `POST` | `/api/keycloak/{domain}/validate/{email}` | Validate a credential, body `{"credentialType": "password"\|"otp", "password": "..."}`. App passwords are rejected. |
| `GET`  | `/api/keycloak/{domain}/configured/{credentialType}/{email}` | Whether the user has the credential type (e.g. `otp`) configured. |

For `otp`, `password` is the 6-digit TOTP code **or** a backup code – a
matching backup code is consumed. See [Keycloak](integrations/keycloak.md#account-status)
for status codes.

## Retention

Scope `retention`. See [Retention](integrations/retention.md).

| Method | Path | Description |
|--------|------|-------------|
| `PUT`  | `/api/retention/{email}/touch` | Update the last login time. Optional body `{"timestamp": <unix time>}`; `400` if in the future. |
| `GET`  | `/api/retention/users` | List users without login for two years. |

## Public endpoints

No authentication required.

| Method | Path | Description |
|--------|------|-------------|
| `GET`  | `/.well-known/openpgpkey/{domain}/hu/{hash}` | [Web Key Directory](wkd.md): binary OpenPGP key |
| `GET`  | `/.well-known/openpgpkey/{domain}/policy` | Web Key Directory policy (empty) |
| `GET`  | `/.well-known/mta-sts.txt` | [MTA-STS](mta-sts.md) policy for the domain in the `Host` header |
