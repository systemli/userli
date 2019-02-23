+++
title = "Installation"
description = ""
weight = 1
+++

The following steps are here to help you instal Userli.
<!--more-->

## Configure prerequisites:

    # Setup database and user
    mysql -e 'CREATE DATABASE mail'
    mysql -e 'CREATE USER `mailuser`@`localhost` IDENTIFIED BY "<password>"'
    mysql -e 'GRANT INSERT,SELECT,UPDATE ON mail.* TO `mailuser`@`localhost`'
    mysql -e 'GRANT DELETE ON mail.virtual_aliases TO `mailuser`@`localhost`'
    mysql -e 'GRANT DELETE ON mail.virtual_vouchers TO `mailuser`@`localhost`'

## Get the code

Install the [latest release](https://github.com/systemli/userli/releases/latest):

    mkdir userli && cd userli
    wget https://github.com/systemli/userli/releases/download/x.x.x/userli-x.x.x.tar.gz
    # Check signature and hash sum, if you know how to
    tar -xvzf userli-x.x.x.tar.gz

    # Copy .env file
    cp .env.dist .env
