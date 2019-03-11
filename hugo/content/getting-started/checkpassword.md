+++
title = "Checkpassword"
description = ""
weight = 5
+++

The console command `bin/checkpassword` is a checkpassword command
to be used for authentication (userdb and passdb lookup) by external services.
So far, it's only tested with Dovecot
<!--more-->

In order to use the userli checkpassword command with Dovecot (< 2.3), the
`default_vsz_limit` (defaults to 256MB) needs to be raised in the Dovecot
configuration. Starting with Dovecot 2.3, the default is 1G.

Example configuration for using checkpassword in Dovecot:

`/etc/dovecot/conf.d/auth-checkpassword.conf.ext`:

    passdb {
      driver = checkpassword
      args = /path/to/userli/bin/checkpassword
    }

    userdb {
      driver = prefetch
    }

    userdb {
      driver = checkpassword
      args = /path/to/userli/bin/checkpassword
    }
