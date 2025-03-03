# MailCrypt

The software has builtin support for
[Dovecot's mailbox encryption](https://doc.dovecot.org/2.3/configuration_manual/mail_crypt_plugin/), using the
[global keys mode](https://doc.dovecot.org/2.3/configuration_manual/mail_crypt_plugin/#global-keys).
Keys are created and maintained by userli and handed over via an API and can
be consumed by Dovecot by a Lua script. See [here](../installation/dovecot.md)
for how to configure authentication and mailcrypt in Dovecot.

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

MailCrypt can be turned on/off for individual users by setting the `mail_crypt`
switch in the `virtual_users` database table. This switch is mainly meant to
provide a migration path from legacy users without MailCrypt keys. On new
setups, it's recommended to keep MailCrypt enabled for all users.
