+++
title = "Finalize the setup"
description = ""
weight = 4
+++

Last steps to make Userli work properly.
<!--more-->

## Create database scheme

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
