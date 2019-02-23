+++
title = "Commands"
description = ""
weight = 4
+++

This app brings custom commands:
<!--more-->
```
app:munin:account          # Return number of account to munin
app:munin:alias            # Return number of aliases to munin
app:munin:voucher          # Return number of vouchers to munin
app:registration:mail      # Send a registration mail to a user
app:report:weekly          # Send weekly report to all admins
app:reservednames:import   # Import reserved names from stdin or file
app:users:check            # Check if user is present
app:users:mailcrypt        # Get MailCrypt values for user
app:users:quota            # Get quota of user if set
app:users:remove           # Removes all mailboxes from deleted users
app:voucher:create         # Create voucher for a specific user
app:voucher:unlink         # Remove connection between vouchers and accounts after 3 months
```
    
Get more information about each command by running:

```
bin/console {{ command }} --help
```
