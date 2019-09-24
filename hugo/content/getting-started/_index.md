+++
title = "Getting Started"
description = ""
weight = 1
alwaysopen = true
+++

The easiest way to install Userli on a fresh Debian Buster is running these commands:

    # install dependencies
    sudo apt install -y python-pip git
    sudo pip install molecule

    # get code
    git clone https://github.com/systemli/ansible-role-userli.git
    cd ansible-role-userli

    # install apache2, mariadb, php7.3 and userli
    sudo molecule converge -s localhost

This installs all dependencies, creates a database and database user
(name: userli, password: userli), and installs the userli code at `/var/www/userli`.
It is accessible via http://localhost:8080.

{{% alert theme="danger" %}}Do not run this configuration in production.{{% /alert %}}

Next, you would have to change the password of the database user,
[configure your instance](../installation/configuration),
and probably install Dovecot to do anything meaningful.

Better, do a [manual installation](../installation) to understand each part of your configuration.
