+++
title = "Update"
description = ""
alwaysopen = true
+++

To update Userli just download the latest version and run these commands:

    # Warm up cache
    bin/console cache:warmup

    # Show database schema updates
    bin/console doctrine:schema:update --dump-sql

    # If necessary update the database schema
    bin/console doctrine:schema:update --force
