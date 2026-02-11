# Finalize the setup

Last steps to make Userli work properly.

## Create database schema

```shell
# Run database migrations
bin/console doctrine:migrations:migrate --no-interaction

# Warm up cache
bin/console cache:warmup
```

## Import reserved names

Reserved names can be managed via the Settings UI under "Reserved Names".
A curated list of common reserved names is available at
[`contrib/reserved_names.txt`](https://github.com/systemli/userli/blob/main/contrib/reserved_names.txt)
and can be imported through the import function in the Settings UI.

## Configure Dovecot

Configure Dovecot to use separate directories per domain and user. Change
the `mail_location` in `10-mail.conf` to something like this:

```text
mail_location = maildir:~/%d/%n
```

## Cronjobs

Some cronjobs are needed in order to run regular tasks. As Userli does not
have write permissions at Dovecot's maildir (usually this directory belongs
to the system user `vmail`) you have to use a [script](https://github.com/systemli/ansible-role-userli/blob/master/templates/userli-maildirs-remove.sh.j2)
to delete a maildir from a removed Userli account:

```text
# Daily create lists of removed mail accounts
@daily userli cd /path/to/userli && bin/console app:users:remove --list --env=prod >/usr/local/share/userli/maildirs-remove.txt

# Daily delete maildirs of removed accounts
@daily /usr/local/bin/userli-maildirs-remove.sh
```
