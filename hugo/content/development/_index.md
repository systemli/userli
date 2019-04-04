+++
title = "Development"
description = ""
weight = 3
alwaysopen = false
+++

## Requirements

* [Vagrant](https://vagrantup.com/)

## Start Vagrant box

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

{{%children style="h2" description="true"%}}
