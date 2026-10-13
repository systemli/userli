# Troubleshooting

## The setup wizard doesn't appear / redirect loop to `/init`

The wizard at `/init` is shown as long as no domain exists. If the database
schema is missing, run the migrations first:

```shell
bin/console doctrine:migrations:migrate --no-interaction
```

## No emails are sent

* Check that the [worker](background-services.md) is running:
  `systemctl status userli-worker`.
* Check the queue and failed messages:

    ```shell
    bin/console messenger:stats
    bin/console messenger:failed:show
    ```

* Check `MAILER_DSN` and the log of your SMTP server.
* Check the **Sender address** in **Admin → Settings**. Your SMTP server must
  accept it.

## Links in emails point to `localhost`

Set `DEFAULT_URI` to the public URL of Userli and restart the worker. Also
check **App URL** in **Admin → Settings**.

## Userli generates `http://` links behind a proxy

Configure [trusted proxies](security.md#reverse-proxies).

## Maintenance tasks don't run

The [scheduler](background-services.md) must be running in addition to the
worker. `bin/console debug:scheduler` shows the next run of each task.

## Dovecot login fails

* Test from the Dovecot host: `doveadm auth test user@example.org`.
* Enable `auth_debug = yes` in Dovecot and check its log.
* Check that `USERLI_HOST` and `USERLI_API_ACCESS_TOKEN` are set and imported
  with `import_environment`.
* Test the API directly:

    ```shell
    curl -H "Authorization: Bearer <TOKEN>" https://users.example.org/api/dovecot/status
    ```

    `{"message":"success"}` means the token works. `401` means the token is
    invalid, `403` that it lacks the `dovecot` scope.

* The account may be deleted, have the role *Spam* or require a password
  change. Check it in **Admin → Users**.

## New users can't receive mail with MailCrypt

Make sure `crypt_write_algorithm` is empty in the global Dovecot
configuration, see [Dovecot → MailCrypt](integrations/dovecot.md#mailcrypt).

## Changes don't reach Postfix immediately

Lookups are cached for a few minutes by Userli and the Postfix adapter. Wait or
restart the adapter.

## Users get "Registration closed"

Invitations are disabled for the domain of the invite code. Enable them in
**Admin → Domains → Edit**, see [Invitations](../admin/domains.md#invitations).

## Cache problems after an update or configuration change

```shell
bin/console cache:clear
```

With several containers but without Redis, each container has its own cache.
Use [Redis](configuration.md#cache).

## Permissions

The webserver/PHP-FPM user needs write access to `var/` (cache and sessions).
All other files can be read-only.

On systems with SELinux, also check the SELinux context of the installation
directory.
