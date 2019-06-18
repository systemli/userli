+++
title = "Checkpassword"
description = ""
weight = 7
+++

The PHP console command `bin/console app:users:checkpassword` provides a
checkpassword command to be used for authentication (userdb and passdb
lookup) by external services. So far, it's only tested with Dovecot.
<!--more-->

In order to use the userli checkpassword command with Dovecot (< 2.3), the
`default_vsz_limit` (defaults to 256MB) needs to be raised in the Dovecot
configuration. Starting with Dovecot 2.3, the default is 1G.

Example configuration for using checkpassword in Dovecot:

`/etc/dovecot/conf.d/auth-checkpassword.conf.ext`:

    passdb {
      driver = checkpassword
      args = /path/to/userli/bin/console app:users:checkpassword
    }

    userdb {
      driver = prefetch
    }

    userdb {
      driver = checkpassword
      args = /path/to/userli/bin/console app:users:checkpassword
    }

## Required permissions and sudo

In order for checkpassword to work as expected, your Dovecot system user needs
read access to the userli application.

In order to grant the required permissions, add the Dovecot system user to the
userli system group:

    adduser dovecot userli
