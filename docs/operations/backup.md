# Backup and restore

## What to back up

| Data | Where | Why |
|------|-------|-----|
| **Database** | MariaDB/MySQL database `userli` | All accounts, aliases, domains, settings, password hashes and the encrypted MailCrypt keys. |
| **Configuration** | `.env.local` or your environment variables | Especially `APP_SECRET` and `DATABASE_URL`. |
| **Customizations** | `translations/` in the installation directory | Overridden texts, if any. |

The application code can be downloaded again and doesn't need a backup. The
cache (`var/cache`) and Redis contain no data that can't be rebuilt.

!!! danger "Database and mailboxes belong together"

    With [MailCrypt](mailcrypt.md), the keys to decrypt the mailboxes are only
    stored in the Userli database. Without a database backup, encrypted
    mailboxes can't be read anymore. Back up the database at least as often as
    the mailboxes and keep both for the same period.

!!! warning "Backups contain sensitive data"

    Database backups contain password hashes and encrypted private keys.
    Encrypt them and restrict access.

## Database backup

```shell
mysqldump --single-transaction --routines --triggers userli | gzip > userli-$(date +%F).sql.gz
```

Run it regularly, for example as a systemd timer or cron job, and copy the
backups to another host.

## Restore

1. Install the same Userli version that was running when the backup was made
   (see [Install from release](installation.md)).
2. Restore the configuration (`.env.local`).
3. Restore the database:

    ```shell
    gunzip -c userli-2025-01-01.sql.gz | mysql userli
    ```

4. Clear the cache and start the services:

    ```shell
    bin/console cache:clear
    ```

5. If you want to update to a newer version, do it afterwards as described in
   [Updating](updating.md).
