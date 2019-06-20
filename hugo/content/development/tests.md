+++
title = "Tests"
description = "How to test during development"
weight = 5
+++

## Linting, unit tests and functional tests

    cd vagrant && vagrant up && vagrant ssh
    make test
    make integration

## Test checkpassword script

    cd vagrant && vagrant up && vagrant ssh
    bin/console doctrine:schema:create -n && bin/console doctrine:fixture:load -n && exit
    cd .. && tests/test_checkpassword_login.sh
