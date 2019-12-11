+++
title = "MailCrypt"
description = ""
weight = 4
+++

The software has builtin support for
[Dovecot's mailbox encryption](https://wiki.dovecot.org/Plugins/MailCrypt), using the
[global keys mode](https://wiki.dovecot.org/Plugins/MailCrypt#Global_keys).
Keys are created and maintained by userli and handed over to Dovecot via
`checkpassword` script.

The MailCrypt feature is enabled per default and can optionally be switched
off globally by setting `MAIL_CRYPT=0` in the dotenv (`.env`) file.

If you want to enable MailCrypt for some users, but don't want new users to
have MailCrypt enabled per default, you can set `MAIL_CRYPT=1` in the dotenv
(`.env`) file. The following values are supported for `MAIL_CRYPT`:

* `MAIL_CRYPT=0` - Disable MailCrypt globally
* `MAIL_CRYPT=1` - Allow to use MailCrypt, needs to be enabled manually for new
                   users
* `MAIL_CRYPT=2` - Enforce MailCrypt key generation for new users
* `MAIL_CRYPT=3` - Enforce MailCrypt key generation for all users, see the
                   documentation about migrating legacy users for more info

In order to enable MailCrypt in Dovecot, the following is required:

* Add `mail_crypt` to the `mail_plugins` list in `/etc/dovecot/conf.d/10-mail.conf`
* Set `mail_crypt_save_version = 0` in `/etc/dovecot/conf.d/90-mail-crypt.conf`

The latter disables MailCrypt per default and is necessary to not break
incoming mail for legacy users without MailCrypt keys. The checkpassword script
automatically sets `mail_crypt_save_version = 2` for all users with MailCrypt
keys.

MailCrypt can be turned on/off for individual users by setting the `mail_crypt`
switch in the `virtual_users` database table. This switch is mainly meant to
provide a migration path from legacy users without MailCrypt keys. On new
setups, it's recommended to keep MailCrypt enabled for all users.

{{%children style="h2" description="true"%}}
