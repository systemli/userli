# Systemli User Management

[![Build Status](https://travis-ci.org/systemli/user-management.svg?branch=master)](https://travis-ci.org/systemli/user-management)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/systemli/user-management/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/systemli/user-management/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/systemli/user-management/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/systemli/user-management/?branch=master)

## Set up development environment

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
    composer test

## Commands

This app brings custom commands:

    usrmgmt:munin:account          # Return number of account to munin
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

## Coding Style

Adjust coding style by running `php-cs-fixer`:

    php-cs-fixer fix src --rules=@Symfony
