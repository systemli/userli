+++
title = "Checkpassword"
description = ""
weight = 5
+++

The console command `bin/checkpassword` is a checkpassword command
to be used for authentication (userdb and passdb lookup) by external services.
So far, it's only tested with Dovecot.
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

## Required permissions and sudo

In order for checkpassword to work as expected, it has to be invoked by a user
with read access to the whole userli project directory and write access to the
`var/` subdirectory.

The checkpassword script brings sudo support for this. In order to use it, do
the following:

1. Add sudoers rules for your dovecot user, allowing them to run the scripts as
   the userli system user:

   `/etc/sudoers.d/userli`:

       # Disable sudo logging for user 'dovecot' to prevent logging of user passwords
       Defaults:dovecot !syslog
       Defaults:dovecot !logfile
       # Allow user 'dovecot' to run some php console commands as user 'userli' 
       dovecot ALL=(userli) NOPASSWD: /usr/bin/php /path/to/userli/bin/console app\:users\:check*
       dovecot ALL=(userli) NOPASSWD: /usr/bin/php /path/to/userli/bin/console app\:users\:mailcrypt*
       dovecot ALL=(userli) NOPASSWD: /usr/bin/php /path/to/userli/bin/console app\:users\:quota*

   Don't forget to set permissions for `/etc/sudoers.d/userli` to 0440:

       chmod 0440 /etc/sudoers.d/userli

2. Configure Dovecot to pass sudo parameters to checkpassword (`-s <user>`,
   where <user> is the userli system user:

   `/etc/dovecot/conf.d/auth-checkpassword.conf.ext`:
   
       passdb {
         driver = checkpassword
         args = /path/to/userli/bin/checkpassword -s userli
       }
   
       userdb {
         driver = prefetch
       }
   
       userdb {
         driver = checkpassword
         args = /path/to/userli/bin/checkpassword -s userli
       }
