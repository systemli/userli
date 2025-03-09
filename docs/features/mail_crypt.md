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

## Implementation details

We use elliptic curve keys with curve secp521r1. The private key is encrypted
with a libargon2i hash of the users' password, stored in a libsodium secret
box.

A second copy of the private key is stored encrypted with a libargon2i hash of
the users' recovery token, to be used when a user restores their account after
they lost their password.

## Migrating legacy users

Legacy users (without MailCrypt keys) continue to work without mailbox
encryption. If they generate a recovery token manually in the account settings,
a MailCrypt key pair gets created for them. This doesn't enable MailCrypt for
them automatically, though. Reason is that this would result in acounts with
partially unencrypted (the old) and partially encrypted (the new) mails.
Therefore we decided to leave the exercise to enable MailCrypt for legacy
users who got a MailCrypt key pair generated in the meantime to the system
admins (e.g. by a cron script).

In order to enable MailCrypt for a legacy user, do the following:

1. Ensure that they have a recovery token generated. This will automatically
   generate MailCrypt key pair as well. This step can only be done by the
   account holder, as the user password is required to do so.
2. Manually set `mail_crypt=1` for the user in the `virtual_users` database
   table. This needs to be done on a per-user basis on purpose (e.g. by a
   cron script).

Or, alternatively, to enforce MailCrypt for all legacy users:

1. Set `MAIL_CRYPT=3` in the dotenv (`.env`) file. This will result in a
   MailCrypt key pair being generated automatically when legacy users log
   in the next time. Again, we cannot do this step without the user logging
   in, as the user password is required to do so.
2. Manually set `mail_crypt=1` for all users in the `virtual_users` database
   table that have a MailCrypt key pair generated but MailCrypt not enabled
   yet. This needs to be done on a per-user basis on purpose (e.g. by a cron
   script).

Please note that existing mails will not be encrypted automatically. Instead,
all existing mail stays unencrypted and only new incoming mail will be stored
encrypted.

In `bin/mailcrypt-encrypt-maildir` you find a script to encrypt unencrypted
mail from legacy mailboxes that already have a MailCrypt key configured. This
script needs to be invoked as a user who has write access to the mailbox in
question - probably the best is to run it as root:

```shell
./bin/mailcrypt-encrypt-maildir user@example.org
```

The following SQL statement can be used to enable MailCrypt for all legacy
users that got a MailCrypt key pair generated. Use with caution!

```sql
UPDATE virtual_users SET mail_crypt=1 WHERE mail_crypt_secret_box IS NOT NULL AND mail_crypt = 0;
```

We might add a migration script to encrypt old mails from existing users at
a later point.
