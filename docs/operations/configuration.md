# Configuration

Userli is configured in two places:

* **Environment variables** for everything the server needs to start –
  database, mail transport, secrets. Described on this page.
* **Settings in the admin interface** for everything that can change at
  runtime – names, URLs, sender addresses, MailCrypt, MTA-STS. See
  [Admin Guide → Settings](../admin/settings.md).

## Environment variables

The file `.env` in the installation directory contains the defaults with
comments. **Don't edit it** – it is replaced on updates. Instead, create
`.env.local` next to it, or set real environment variables (for example in
containers or systemd units). Real environment variables take precedence over
both files.

### Required

| Variable       | Description | Example |
|----------------|-------------|---------|
| `APP_ENV`      | Environment. Always `prod` on servers. | `prod` |
| `APP_SECRET`   | Random secret used for CSRF tokens and to sign queued messages. Keep it secret and stable. | output of `openssl rand -hex 32` |
| `DATABASE_URL` | Database connection. Add `serverVersion` to match your database. | `mysql://userli:pass@127.0.0.1:3306/userli?serverVersion=mariadb-10.11.0` |
| `MAILER_DSN`   | How Userli sends emails, see [Symfony Mailer](https://symfony.com/doc/current/mailer.html#transport-setup). | `smtp://localhost:25` |

### Recommended

| Variable                  | Description | Example |
|---------------------------|-------------|---------|
| `DEFAULT_URI`             | Public base URL. Needed to generate correct links in emails sent by the worker. | `https://users.example.org` |
| `MESSENGER_TRANSPORT_DSN` | Queue for asynchronous messages. The default in `.env` uses the database; Redis or AMQP are possible, see [Symfony Messenger](https://symfony.com/doc/current/messenger.html#transport-configuration). | `redis://localhost:6379/messages` |
| `REDIS_URL`               | Enables [Redis as cache](#cache). | `redis://localhost:6379` |
| `SYMFONY_TRUSTED_PROXIES` | IP addresses or ranges of reverse proxies in front of Userli, see [Security](security.md#reverse-proxies). | `127.0.0.1,REMOTE_ADDR` |

!!! warning "Changing `APP_SECRET`"

    Messages in the queue are signed with `APP_SECRET`. If you change it, let
    the worker process all queued messages first, otherwise they are rejected.

### Removed variables

These variables were used by older versions and are ignored now. Remove them
from your configuration:

| Variable | Replaced by |
|----------|-------------|
| `REGISTRATION_OPEN` | [Invitations per domain](../admin/domains.md#invitations) (6.6.0) |
| `MAIL_CRYPT` | Setting [*MailCrypt*](../admin/settings.md#mailbox-encryption) (6.4.0) |
| `WEBMAIL_URL` | Setting [*Webmail URL*](../admin/settings.md#application-settings) (6.2.0) |
| `DOVECOT_API_*`, `KEYCLOAK_API_*`, `RETENTION_API_*` | [API tokens](../admin/api-tokens.md) |

## Cache

By default, Userli uses the filesystem for caching. This works well for small
installations on a single host.

For larger installations, Redis is recommended as a shared cache backend. Set
`REDIS_URL`:

```text
REDIS_URL=redis://localhost:6379
```

When `REDIS_URL` is set, Userli automatically switches from filesystem to
Redis for all caching (application cache, Doctrine result cache, scheduler
state, etc.).

!!! note

    The PHP Redis extension (`phpredis`) must be installed for the Redis
    adapter to work. It is included in the official container image.

!!! warning

    If you run multiple containers (web, worker, scheduler) without Redis,
    each container maintains its own isolated filesystem cache. This means
    cache invalidation in one container does not affect the others.

After changing environment variables, clear the cache:

```shell
bin/console cache:clear
```
