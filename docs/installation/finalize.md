# Finalize the setup

Last steps to make Userli work properly.

## Create database schema

```shell
# Run database migrations
bin/console doctrine:migrations:migrate --no-interaction

# Warm up cache
bin/console cache:warmup
```

## Initial setup

Open Userli in your browser. As long as no domain exists, you are redirected to
the setup wizard at `/init`, which guides you through three steps:

1. Create the first mail domain.
2. Set the password of the first admin account `postmaster@<domain>`.
3. Review the global settings (names, URLs, sender addresses, ...).

Afterwards, log in with the new admin account. You can also set the admin
password on the command line with `bin/console app:admin:password`.

## Import reserved names

Reserved names can be managed in the admin interface under **Admin → Reserved Names**.
A curated list of common reserved names is available at
[`contrib/reserved_names.txt`](https://github.com/systemli/userli/blob/main/contrib/reserved_names.txt)
and can be imported through the import function there.

## Configure Dovecot

Configure Dovecot to use separate directories per domain and user. Change
the `mail_location` in `10-mail.conf` to something like this:

```text
mail_location = maildir:~/%d/%n
```

## Background services

Userli sends emails (e.g. the welcome mail) and webhooks asynchronously and
runs recurring maintenance tasks (e.g. removing inactive users, sending the
weekly report). Both require two long-running processes besides the webserver:

```shell
# Worker for asynchronous messages
bin/console messenger:consume async --time-limit=3600 -vv

# Scheduler for recurring maintenance tasks
bin/console messenger:consume scheduler_maintenance --time-limit=3600 -vv
```

Run them under a process supervisor such as systemd, which restarts them when
they exit after the time limit. The messages are stored in the database by default (`MESSENGER_TRANSPORT_DSN`
in `.env`); Redis or AMQP can be configured in `.env.local` instead.

!!! note

    Without the worker, no emails or webhooks are sent. Without the scheduler,
    the maintenance tasks are not executed. They can also be triggered manually
    in the admin interface under **Admin → Maintenance**.

## Removing mailboxes of deleted users

Userli has no write access to Dovecot's maildirs (usually owned by the system
user `vmail`) and therefore does not delete mailboxes itself. To remove the
maildir of a deleted account, react to the `user.deleted`
[webhook](../features/webhooks.md) with a script on the mail server.
