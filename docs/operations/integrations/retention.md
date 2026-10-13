# Retention

Userli removes accounts that were not used for about two years (see
[Maintenance](../../admin/maintenance.md)). Every sign-in – web, Dovecot,
Postfix, Keycloak – updates the *last login* time of a user.

Some services issue long-lived sessions or tokens and don't authenticate
against Userli regularly. Their users might look inactive although they use the
service every day. The Retention API solves this:

* **Touch:** report that a user was active in your service.
* **List inactive users:** find users without login for two years, for
  example to clean up their data in your service.

See [userli-synapse-user-retention](https://github.com/systemli/userli-synapse-user-retention)
for an example implementation for Matrix Synapse.

## On the Userli host

Create an API token with the scope `retention`:

```shell
bin/console app:api-token:create --name "Synapse retention" --scopes retention
```

## Touch last login

```text
PUT /api/retention/<email>/touch
Authorization: Bearer <PLAIN_TOKEN>
Content-Type: application/json

{ "timestamp": 1693843200 }
```

`timestamp` is an optional Unix timestamp of the last activity. It must not be
in the future. Without it, the current time is used.

## List inactive users

```text
GET /api/retention/users
Authorization: Bearer <PLAIN_TOKEN>
```

Returns the email addresses of users without login for two years.
