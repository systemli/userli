# Updating

Userli follows [semantic versioning](https://semver.org/). Read the release
notes before every update:

* [`CHANGELOG.md`](https://github.com/systemli/userli/blob/main/CHANGELOG.md) –
  all changes per release
* [`UPGRADE.md`](https://github.com/systemli/userli/blob/main/UPGRADE.md) –
  manual steps required for a release, for example removed environment
  variables or changed Dovecot configuration

Don't skip the notes of intermediate versions when updating over several
releases.

## Update a tarball installation

1. Back up the database (see [Backup and restore](backup.md)).
2. Stop the [background services](background-services.md) and let the queue
   drain if `UPGRADE.md` says so.
3. Download and verify the new release, see
   [Install from release](installation.md#1-download).
4. Unpack it into a new directory and copy your `.env.local` (and
   `translations/`, if you [customized](customization.md) texts) into it.
5. Run the migrations and warm up the cache:

    ```shell
    bin/console doctrine:migrations:migrate --no-interaction
    bin/console cache:clear
    ```

6. Switch the webserver to the new directory (for example with a symlink
   `/var/www/userli → /var/www/userli-x.y.z`) and reload PHP-FPM.
7. Start the background services again.
8. Update the [Dovecot adapter](integrations/dovecot.md) to the same version.

Using one directory per version makes rollbacks of the code easy. Rolling back
database migrations is usually not possible – restore the backup instead.

## Update a container installation

See [Run with containers](docker.md#updating).

## Check the migration status

```shell
bin/console doctrine:migrations:status
```
