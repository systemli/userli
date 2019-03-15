+++
title = "Migrating legacy users"
description = ""
weight = 1
+++

Legacy users (without MailCrypt keys) continue to work without mailbox
encryption. If they generate a recovery token manually in the account settings,
a MailCrypt key pair gets created for them. This doesn't enable MailCrypt for 
them automatically, though.
<!--more-->

In order to enable MailCrypt for a legacy user, do the following:

1. Ensure that they have a recovery token generated. This will automatically
   generate MailCrypt key pair as well. This step can only be done by the
   account holder, as the user password is required to do so.
2. Manually set `mailCrypt=1` for the user in the `virtual_users` database
   table. This needs to be done manually on a per-user basis on purpose.

Please note that existing mails will not be encrypted automatically. Instead,
all existing mail stays unencrypted and only new incoming mail will be stored
encrypted.

In `bin/mailcrypt-encrypt-maildir` you find a script to encrypt unencrypted
mail from legacy mailboxes that already have a MailCrypt key configured. This
script needs to be invoked as a user who has write access to the mailbox in
question - probably the best is to run it as root:

    ./bin/mailcrypt-encrypt-maildir user@example.org

The following SQL statement can be used to enable MailCrypt for all legacy
users that generated a recovery token in the meantime (and thus have a
MailCrypt key). Use with caution!

    UPDATE virtual_users SET mailCrypt=1 WHERE recoverySecretBox IS NOT NULL AND mailCryptSecretBox IS NOT NULL;

We might add a migration script to encrypt old mails from existing users at
a later point.
