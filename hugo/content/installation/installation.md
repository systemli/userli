+++
title = "Installation"
description = ""
weight = 1
+++

The following steps are here to help you instal Userli.
<!--more-->

## Configure prerequisites:

    # Setup database and user
    mysql -e 'CREATE DATABASE userli'
    mysql -e 'CREATE USER `userli`@`localhost` IDENTIFIED BY "<password>"'
    mysql -e 'GRANT ALL PRIVILEGES ON userli.* TO `userli`@`localhost`'

## Get the code

Install the [latest release](https://github.com/systemli/userli/releases/latest):

    mkdir userli && cd userli
    wget https://github.com/systemli/userli/releases/download/x.x.x/userli-x.x.x.tar.gz
    # Check signature and hash sum, if you know how to
    tar -xvzf userli-x.x.x.tar.gz
