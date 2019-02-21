# Userli

[![Build Status](https://travis-ci.org/systemli/userli.svg?branch=master)](https://travis-ci.org/systemli/userli)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/systemli/userli/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/systemli/userli/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/systemli/userli/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/systemli/userli/?branch=master)

Web application to (self-) manage email users and encrypt their mailboxes.

## Features

* User self-service (change password/recovery token, set aliases, ...)
* Invite code system (new users get three invite codes after one week)
* Domain admins (accounts with admin rights for one domain)
* Random alias feature for users
* Recovery tokens to restore accounts when password got lost
* Support for [Dovecot mailbox encryption](https://wiki.dovecot.org/Plugins/MailCrypt)

## Upgrading

Please see `UPGRADE.md` for instructions on how to upgrade from an earlier
release.

## Production deployment

* Requirements:
  * [PHP >= 7.1](https://secure.php.net/)
  * [MariaDB](https://mariadb.org/) or [MySQL](https://mysql.com/)
  * [libsodium](https://download.libsodium.org/doc/)
  * [OpenSSL](https://www.openssl.org/) binary (for MailCrypt feature)

  Libsodium is already included in PHP 7.2. You can also run this application with PostgreSQL oder SQLite.

Configure prerequisites:

    # Setup database and user
    mysql -e 'CREATE DATABASE mail'
    mysql -e 'CREATE USER `mailuser`@`localhost` IDENTIFIED BY "<password>"'
    mysql -e 'GRANT INSERT,SELECT,UPDATE ON mail.* TO `mailuser`@`localhost`'
    mysql -e 'GRANT DELETE ON mail.virtual_aliases TO `mailuser`@`localhost`'
    mysql -e 'GRANT DELETE ON mail.virtual_vouchers TO `mailuser`@`localhost`'

Get the [latest release](https://github.com/systemli/userli/releases/latest):

    mkdir userli && cd userli
    wget https://github.com/systemli/userli/releases/download/x.x.x/userli-x.x.x.tar.gz
    # Check signature and hash sum, if you know how to
    tar -xvzf userli-x.x.x.tar.gz

    # Copy .env file
    cp .env.dist .env

Configure the application in `.env`:

    APP_ENV=prod
    APP_NAME=Userli
    APP_SECRET=<random secret string>
    APP_URL=https://users.example.org/
    DATABASE_DRIVER=pdo_mysql
    DATABASE_URL=mysql://mailuser:<password>@127.0.0.1:3306/mail
    MAILER_URL=smtp://localhost:25?encryption=&auth_mode=
    PROJECT_NAME=example.org
    PROJECT_URL=https://www.example.org/
    DOMAIN=example.org
    SENDER_ADDRESS=userli@example.org
    NOTIFICATION_ADDRESS=admin@example.org
    SEND_MAIL=true
    LOCALE=en
    HAS_SINA_BOX=false
    MAIL_CRYPT_ENABLED=1

Finalize setup:

    # Create default database schema
    bin/console doctrine:schema:create

    # Load default reserved names into database
    bin/console app:reservednames:import

    # Warm up cache
    bin/console cache:warmup

## Cronjobs

Some cronjobs are needed in order to run regular tasks:

	# Daily purge data from deleted mail users
	@daily userli cd /path/to/userli && bin/console app:users:remove -q

	# Daily unlink old redeemed vouchers
	@daily userli cd /path/to/userli && bin/console app:voucher:unlink

	# Send weekly report to admins
	12 13 * * 1 userli cd /path/to/userli && bin/console app:report:weekly

## Development environment

Requirements: Vagrant (VirtualBox)

    git submodule update --init
    cd vagrant && vagrant up && vagrant ssh

    # create database and schema
    bin/console doctrine:schema:create
    
    # load sample data
    bin/console doctrine:fixtures:load

    # get node modules
    yarn

    # update assets
    yarn encore dev

The `doctrine:fixtures:load` command will create four new accounts with
corresponding roles (`admin`, `user`, `support`, `suspicious`) as well
as some random aliases and vouchers. The domain for all accounts is
"example.org" and the password is "password".

If you want to see more details about how the users are created, see
`src/DataFixtures`.

Visit you local instance at http://192.168.33.99/.

## Tests

    cd vagrant && vagrant up && vagrant ssh
    make test

## Commands

This app brings custom commands:

    app:munin:account          # Return number of account to munin
    app:munin:alias            # Return number of aliases to munin
    app:munin:voucher          # Return number of vouchers to munin
    app:registration:mail      # Send a registration mail to a user
    app:report:weekly          # Send weekly report to all admins
    app:reservednames:import   # Import reserved names from stdin or file
    app:users:check            # Check if user is present
    app:users:mailcrypt        # Get MailCrypt values for user
    app:users:quota            # Get quota of user if set
    app:users:remove           # Removes all mailboxes from deleted users
    app:voucher:create         # Create voucher for a specific user
    app:voucher:unlink         # Remove connection between vouchers and accounts after 3 months
    
Get more information about each command by running:

    bin/console {{ command }} --help

## Customizations

You can override translation strings individually by putting them into
override localization files at `translations/<lang>/messages.<lang>.yml`.

## Using `checkpassword` command

The console command `bin/checkpassword` is a checkpassword command
to be used for authentication (userdb and passdb lookup) by external services.
So far, it's only tested with Dovecot.

In order to use the userli checkpassword command with Dovecot (< 2.3), the
`default_vsz_limit` (defaults to 256MB) needs to be raised in the Dovecot
configuration. Starting with Dovecot 2.3, the default is 1G.

Example configuration for using checkpassword in Dovecot:

`/etc/dovecot/conf.d/auth-checkpassword.conf.ext`:

    passdb {
      driver = checkpassword
      args = /path/to/userli/bin/checkpassword
    }

    userdb {
      driver = prefetch
    }

    userdb {
      driver = checkpassword
      args = /path/to/userli/bin/checkpassword
    }

## Support for [Dovecot's MailCrypt plugin](https://wiki.dovecot.org/Plugins/MailCrypt)

The software has builtin support for Dovecot's mailbox encryption, using the
[global keys mode](https://wiki.dovecot.org/Plugins/MailCrypt#Global_keys).
Keys are created and maintained by userli and handed over to Dovecot via
`checkpassword` script.

The MailCrypt feature is enabled per default and can optionally be switched
off globally by setting `MAIL_CRYPT_ENABLE=0` in the dotenv (`.env`) file.

If you want to enable MailCrypt for some users, but don't want new users to
have MailCrypt enabled per default, you can set `MAIL_CRYPT_AUTO=0` in the
dotenv (`.env`) file.

In order to enable MailCrypt in Dovecot, the following is required:

* Add `mail_crypt` to the `mail_plugins` list in `/etc/dovecot/conf.d/10-mail.conf`
* Set `mail_crypt_save_version = 0` in `/etc/dovecot/conf.d/90-mail-crypt.conf`

The latter disables MailCrypt per default and is necessary to not break
incoming mail for legacy users without MailCrypt keys. The checkpassword script
automatically sets `mail_crypt_save_version = 2` for all users with MailCrypt
keys.

MailCrypt can be turned on/off for individual users by setting the `mailCrypt`
switch in the `virtual_users` database table. This switch is mainly meant to
provide a migration path from legacy users without MailCrypt keys. On new
setups, it's recommended to keep MailCrypt enabled for all users.

### Migrating legacy users to mailbox encryption

Legacy users (without MailCrypt keys) continue to work without mailbox
encryption. If they generate a recovery token manually in the account settings,
a MailCrypt key pair gets created for them. This doesn't enable MailCrypt for
them automatically, though.

In order to enable MailCrypt for a legacy user, do the following:

1. Ensure that they have a recovery token generated. This will automatically
   generate MailCrypt key pair as well. This step can only be done by the
   account holder, as the user password is required to do so.
2. Manually set `mailCrypt=1` for the user in the `virtual_users` database
   table. This needs to be done manually on a per-user basis on purpose.

Please note that existing mails will not be encrypted automatically. Instead,
all existing mail stays unencrypted and only new incoming mail will be stored
encrypted.

The following SQL statement can be used to enable MailCrypt for all legacy
users that generated a recovery token in the meantime (and thus have a
MailCrypt key). Use with caution!

    UPDATE virtual_users SET mailCrypt=1 WHERE recoverySecretBox IS NOT NULL AND mailCryptSecretBox IS NOT NULL;

We might add a migration script to encrypt old mails from existing users at
a later point.

### MailCrypt implementation details

We use elliptic curve keys with curve secp521r1. The private key is encrypted
with a libargon2i hash of the users' password, stored in a libsodium secret
box.

A second copy of the private key is stored encrypted with a libargon2i hash of
the users' recovery token, to be used when a user restores their account after
they lost their password.

## Creating release tarballs

First, you'll need a [Github API token](https://github.com/settings/tokens).
The token needs the following priviledges:

    public_repo, repo:status, repo_deployment

Now, execute the following script. It will create a version tag, release and
copy the info from `CHANGELOG.md` to the release info.

    $ GITHUB_API_TOKEN=<token> GPG_SIGN_KEY="<key_id>" ./bin/github-release.sh <version>

## Coding Style

Adjust coding style by running `php-cs-fixer`:

    make cs-fixer

## Copyright

Files: *  
Copyright: (c) 2015-2019 systemli.org  
License: AGPL v3 or later  

Files: assets/images/*.svg  
Copyright: (c) github.com  
License: MIT
