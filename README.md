# Systemli User Management

[![Build Status](https://travis-ci.org/systemli/user-management.svg?branch=master)](https://travis-ci.org/systemli/user-management)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/systemli/user-management/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/systemli/user-management/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/systemli/user-management/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/systemli/user-management/?branch=master)

## Production deployment

* Requirements:
  * [PHP >= 7.1](https://secure.php.net/)
  * [MariaDB](https://mariadb.org/) or [MySQL](https://mysql.com/)
  * [libsodium](https://download.libsodium.org/doc/)

  Libsodium is already included in PHP 7.2. You can also run this application with PostgreSQL oder SQLite.

Configure prerequisites:

    # Setup database and user
    mysql -e 'CREATE DATABASE mail'
    mysql -e 'CREATE USER `mailuser`@`localhost` IDENTIFIED BY "<password>"'
    mysql -e 'GRANT INSERT,SELECT,UPDATE ON mail.* TO `mailuser`@`localhost`'
    mysql -e 'GRANT DELETE ON mail.virtual_aliases TO `mailuser`@`localhost`'
    mysql -e 'GRANT DELETE ON mail.virtual_vouchers TO `mailuser`@`localhost`'

Get the [latest release](https://github.com/systemli/user-management/releases/latest):

    mkdir user-management && cd user-management 
    wget https://github.com/systemli/user-management/releases/download/1.x.0/user-management-1.x.0.tar.gz
    # Check signature and hash sum, if you know how to
    tar -xvzf user-management-1.x.0.tar.gz

    # Copy .env file
    cp .env.dist .env

Configure the application in `.env`:

    APP_ENV=prod
    APP_NAME=User Management
    APP_SECRET=<random secret string>
    APP_URL=https://users.example.org/
    DATABASE_DRIVER=pdo_mysql
    DATABASE_URL=mysql://mailuser:<password>@127.0.0.1:3306/mail
    MAILER_URL=smtp://localhost:25?encryption=&auth_mode=
    MAILER_DELIVERY_ADDRESS=admin@example.org
    PROJECT_NAME=example.org
    PROJECT_URL=https://www.example.org/
    DOMAIN=example.org
    SENDER_ADDRESS=admin@example.org
    NOTIFICATION_ADDRESS=monitoring@example.org
    SEND_MAIL=true
    LOCALE=en
    HAS_SINA_BOX=false

Finalize setup:

    # Create default database schema
    bin/console doctrine:schema:create

    # Load default reserved names into database
    bin/console usrmgmt:reservednames:import

    # Warm up cache
    bin/console cache:warmup

## Cronjobs

Some cronjobs are needed in order to run regular tasks:

	# Daily purge data from deleted mail users
	@daily usermgmt cd /path/to/user-management && bin/console usrmgmt:users:remove -q

	# Daily unlink old redeemed vouchers
	@daily usermgmt cd /path/to/user-management && bin/console usrmgmt:voucher:unlink

	# Send weekly report to admins
	12 13 * * 1 usermgmt cd /path/to/user-managment && bin/console usrmgmt:report:weekly

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

    usrmgmt:munin:account          # Return number of account to munin
    usrmgmt:munin:alias            # Return number of aliases to munin
    usrmgmt:munin:voucher          # Return number of vouchers to munin
    usrmgmt:registration:mail      # Send registration mail to user
    usrmgmt:report:weekly          # Send weekly report about registrations
    usrmgmt:reservednames:import   # Import reserved names from stdin or text file
    usrmgmt:users:checkpassword    # Checkpassword script for user authentication
    usrmgmt:users:remove           # Remove disabled users maildirs
    usrmgmt:voucher:create         # Create multiple vouchers for user, -c configures amount
    usrmgmt:voucher:unlink         # Unlink redeemed vouchers from users
    
Get more information about each command by running:

    bin/console {{ command }} --help

## Customizations

You can override translation strings individually by putting them into
override localization files at `translations/<lang>/messages.<lang>.yml`.

## Using `checkpassword` command

The console command `usrmgmt:users:checkpassword` is a checkpassword command
to be used for authentication (userdb and passdb lookup) by external services.
So far, it's only tested with Dovecot.

In order to use the usrmgmt checkpassword command with Dovecot (< 2.3), the
`default_vsz_limit` (defaults to 256MB) needs to be raised in the Dovecot
configuration. Starting with Dovecot 2.3, the default is 1G.

Example configuration for using checkpassword in Dovecot:

/etc/dovecot/conf.d/auth-checkpassword.conf.ext:

    passdb {
      driver = checkpassword
      args = /usr/bin/php7.1 /path/to/usrmgmt/bin/console usrmgmt:users:checkpassword
    }

    userdb {
      driver = prefetch
    }

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

## License

Files: *
Copyright: (c) 2015-2018 systemli.org
License: AGPL v3 or later

Files: assets/images/*.svg
Copyright: (c) github.com
License: MIT
