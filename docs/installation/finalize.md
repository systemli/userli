# Finalize the setup

Last steps to make Userli work properly.

## Create database scheme

    # Create default database schema
    bin/console doctrine:schema:create

    # Load default reserved names into database
    bin/console app:reservednames:import

    # Warm up cache
    bin/console cache:warmup

## Configure Dovecot

Configure Dovecot to use separate directories per domain and user. Change
the `mail_location` in `10-mail.conf` to something like this:

	mail_location = maildir:~/%d/%n

## Cronjobs

Some cronjobs are needed in order to run regular tasks. As Userli does not
have write permissions at Dovecot's maildir (usually this directory belongs
to the system user `vmail`) you have to use a [script](https://github.com/systemli/ansible-role-userli/blob/master/templates/userli-maildirs-remove.sh.j2)
to delete a maildir from a removed Userli account:

	# Daily create lists of removed mail accounts
	@daily userli cd /path/to/userli && bin/console app:users:remove --list --env=prod >/usr/local/share/userli/maildirs-remove.txt

	# Daily delete maildirs of removed accounts
	@daily /usr/local/bin/userli-maildirs-remove.sh

	# Daily unlink old redeemed vouchers
	@daily userli cd /path/to/userli && bin/console app:voucher:unlink

	# Send weekly report to admins
	12 13 * * 1 userli cd /path/to/userli && bin/console app:report:weekly
