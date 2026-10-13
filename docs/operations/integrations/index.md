# Mail server integration

Userli doesn't need access to the mail server, and the mail server doesn't need
access to the Userli database. All communication goes through authenticated
HTTP APIs: the mail server asks Userli, Userli answers.

| Service    | Integration | API scope | Required |
|------------|-------------|-----------|----------|
| Dovecot    | [Lua adapter](dovecot.md) shipped with Userli | `dovecot` | yes, for IMAP/POP3 |
| Postfix    | [userli-postfix-adapter](postfix.md) | `postfix` | yes, for SMTP |
| Roundcube  | [Roundcube plugin](roundcube.md) | `roundcube` | optional |
| Keycloak   | [Keycloak user provider](keycloak.md) | `keycloak` | optional |
| Other services | [Retention API](retention.md) | `retention` | optional |
| Other services | [Webhooks](webhooks.md) | – | optional |
| Dovecot (mailbox cleanup) | [userli-mailbox-janitor](mailbox-janitor.md) | `retention` | optional |

## API tokens

Every integration authenticates with an API token. Each token has one or more
scopes that define which API it may use. Create tokens in the admin interface
under **Admin → API Tokens** (see [Admin Guide → API tokens](../../admin/api-tokens.md))
or on the command line:

```shell
# Create a token for Dovecot
bin/console app:api-token:create --name "Dovecot mx1" --scopes dovecot

# Create a token with several scopes
bin/console app:api-token:create --name "Mail host" --scopes dovecot --scopes postfix

# Delete a token
bin/console app:api-token:delete --token <PLAIN_TOKEN>
```

Send the token with each request:

```text
Authorization: Bearer <PLAIN_TOKEN>
```

`X-API-Token: <PLAIN_TOKEN>` is accepted as a fallback.

* Tokens are stored hashed; the plain token is shown only once.
* A token without the required scope gets HTTP `403`.
* The last use of each token is shown in the admin interface.

Use one token per service and host with only the scopes it needs. All API
endpoints are listed in the [API reference](../api.md).

## Account status and the mail server

The APIs enforce the account status, so the mail server doesn't need its own
logic:

| Account state | IMAP/POP3 login (Dovecot) | SMTP auth (Postfix) | Incoming mail |
|---------------|:-------------------------:|:-------------------:|:-------------:|
| active        | ✓ | ✓ | ✓ |
| password change required | ✗ | ✗ | ✓ |
| role *Spam*   | ✗ | ✗ | ✓ |
| deleted       | ✗ | ✗ | ✗ |

!!! warning "Replaced by scoped API tokens"

    The settings `DOVECOT_API_*`, `KEYCLOAK_API_*` and `RETENTION_API_*`
    (enable flags, IP allowlists and access tokens) have been removed. Use
    scoped API tokens instead.
