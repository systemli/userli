+++
title = "Create database"
description = ""
weight = 1
+++

Create Userli database and database user.
<!--more-->
For simplicity, the user has full access to `userli` database.

    mysql -e 'CREATE DATABASE userli'
    mysql -e 'CREATE USER `userli`@`localhost` IDENTIFIED BY "<password>"'
    mysql -e 'GRANT ALL PRIVILEGES ON userli.* TO `userli`@`localhost`'
