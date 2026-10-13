# Userli

Web application to (self-) manage e-mail users and encrypt their mailboxes.

![index](./assets/images/index.png){class="screenshot"}

## Features

* User self-service (change password/recovery token, set aliases, ...)
* Invite code system (new users get three invite codes after one week)
* Domain admins (accounts with admin rights for one domain)
* Random alias feature for users
* Recovery tokens to restore accounts when password got lost
* Support for [Dovecot mailbox encryption](https://doc.dovecot.org/main/core/plugins/mail_crypt.html)
* Two-factor authentication (TOTP) with backup codes
* OpenPGP key publishing via [Web Key Directory](https://systemli.github.io/userli/features/wkd/)
* Admin interface for users, aliases, domains, invite codes, API tokens and webhooks
* APIs for Dovecot, Postfix, Keycloak and Roundcube, and [webhooks](https://systemli.github.io/userli/features/webhooks/)
* [MTA-STS](https://systemli.github.io/userli/features/mta-sts/) policy hosting
* Multi-language support (English, German, Spanish, French, Italian, Portuguese, Bokmål, and Swiss German provided)

## Roles

Userli supports a role system to help you run your mail server.

* User - Default role
* Multiplier - Like user but with unlimited invite codes
* Suspicious - User without invite codes
* Spam - This account is suspected to be hacked and can't send mail anymore
* Permanent - Don't delete this account in user cleanup 
* Domain-Admin - Can add/edit/delete users and aliases for their domain
* Admin - Can add/edit/delete all available data

## Contribute

This is a start. Please help to [improve the documentation](https://github.com/systemli/userli/edit/main/docs/index.md).
