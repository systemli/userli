# 2.7.15 (2021.08.06)

* Show correct random alias without reload (Fixes #307)
* Make php7.3 serve traffic in vagrant
* Update PHP and JS dependencies
* Rename default git branch to `main`
* Include default translations

# 2.7.14 (2021.08.04)

* Don't print info line in RemoveUsersCommand with `--list`

# 2.7.13 (2021.08.04)

* Add `--list` option to RemoveUsersCommand to list maildir directories
* Add psalm static analysis CI job
* Add contributors to README.md

# 2.7.12 (2021.06.25)

* Fix query logic when listing inactive users in findInactiveUsers()

# 2.7.11 (2021.06.25)

* Update PHP and JS dependencies
* Add ROLE_PERMANANT to be used for excluding accounts in user cleanup
* Update ansible roles to fix playbook run
* Add console command `app:users:list` with option to list inactive users
* Dispatch AliasCreatedEvent after validation (Fixes: #216)

# 2.7.10 (2021.05.20)

* Really fix CVE-2021-21424
* Upgrade to symfony 4.4.24
* Update PHP and JS dependencies

# 2.7.9 (2021.05.14)

* Fix CVE-2021-21424
* Upgrade to symfony 4.4.23
* Update PHP and JS dependencies

# 2.7.8 (2021.03.08)

* Typo fixes
* Update dependencies
* Add placeholder for recovery code (Thanks xshadow)

# 2.7.7 (2020.11.25)

* Limit permissions to set admin role to admin users
* Move mail location and dovecot UID/GID settings to environment
  variables (Thanks 1resu)
* Show footer on recovery page (Fixes: #141, thanks 1resu)
* Add hint to clear the cache to docs (Thanks 1resu)
* Open homepage link in same window (Thanks 1resu)
* Change username input type in login forms (Thanks 1resu)

# 2.7.6 (2020.11.16)

* Checkpassword: Don't throw an Exception on missing password

# 2.7.5 (2020.11.10)

* Always initiate Web Key Directory for new domains
* Improve style of pasted OpenPGP key (Thanks trashcan55)

# 2.7.4 (2020.10.27)

* Fix verification of invite codes

# 2.7.3 (2020.10.21)

* Improve OpenPGP key import filter:
  - Keep UIDs with valid email address but without realname
  - Drop UIDs with invalid email address that have the valid email
    address in realname

# 2.7.2 (2020.10.21)

* Fix Munin account stats
* Fix overwriting existing OpenPGP keys
* Upgrade Travis CI for bionic and PHP7.3

# 2.7.1 (2020.10.20)

* Downgrade `twbs/bootstrap` back to 3.3

# 2.7.0 (2020.10.20)

* Add support to import OpenPGP keys and export them to an
  OpenPGP Web Key Directory.
* Drop support for PHP 7.1 and 7.2
* Allow to batch delete users (Fixes: #78)
* Update Portugues translation (Thanks Silvério Santos)
* Update dependencies

# 2.6.1 (2020.09.10)

* Allow mails to be delivered to suspected spammers (Fixes: #212)
* Update to symfony 4.4
* Update translations

# 2.6.0 (2020.05.12)

* Attempt to fix mail body translation string format (Fixes: #205)
* Add new console command `app:users:delete` to delete users.

# 2.5.1 (2020.01.09)

* Fix userDB lookups for accounts without mail_crypt when MAIL_CRYPT=3.
* Add French translation (Thanks Nathan Bonnemains)

# 2.5.0 (2020.01.05)

* Merge `MAIL_CRYPT_*` dotenv variables into a single one. Please
  see `UPGRADING.md` and the documentation for further information.
* Update to symfony 4.3.9
* Update Norwegian translation (Thanks Alan Nordhøy)
* Allow to create first domain and account via web frontend (Fixes: #195)
* Automatically create `postmaster@domain` for all domains (Fixes: #111)

# 2.4.0 (2019.11.18)

* Skip check against HIBP service if unavailable
* Add copy-to-clipboard for aliases (Fixes: #181) (Thanks @jelhan)
* Add Swiss German to supported languages (Thanks wee)
* Update dependencies

# 2.3.0 (2019.07.16)

* Add manual language switcher (Fixes: #172)
* Add Norwegian Bokmål as available translation
* Block Spammers from authenticating via checkpassword (Fixes: #177)
* Test passwords againt haveibeenpwned database (Fixes: #161)
* Upgrade to symfony 4.3.2
* Improve speed of Vagrant box

# 2.2.3 (2019.06.24)

* Repair js copying of invite codes (Fixes: #165)
* Several minor language fixes (Thanks to ssantos)
* Start Norwegian translation (Thanks to Allan Nordhøy)
* Switch to PHP-only checkpassword script for security reasons. This
  eliminates the requirement to setup sudo. See the updated docs for
  details.

# 2.2.2 (2019.06.14)

* Delete aliases when deleting user (Fixes: #121)
* Fix error when trying to register deleted username (Fixes: #176)
* Remove link to registration from right navbar
* Update PHP and JS dependecies

# 2.2.1 (2019.06.06)

* Add org/organisation/... to reserved names
* Update to symfony 4.2.9
* Update PHP and JS dependecies
* Rename ROLE_SUPPORT to ROLE_MULTIPLIER

# 2.2.0 (2019.05.22)

* Add initial Spanish translation
* Add initial Portuguese translation (Thanks to Bruno Gama)
* Add plural forms of many reserved names
* Update to symfony 4.2.8
* Fix mailcrypt-encrypt-maildir script for paths with whitespaces
* Fix release tarball creation, don't use tar option --transform

# 2.1.2 (2019.04.18)

* Create release tarball in subdirectory
* Add optional link to webmail (Fixes: #146)
* Update to symfony 4.2.7

# 2.1.1 (2019.03.17)

* Change default locale setting to 'en'
* Don't resolve symlinks to not break sudo in checkpassword

# 2.1.0 (2019.03.17)

* New shell script `bin/mailcrypt-encrypt-maildir` to encrypt legacy mailboxes
* Update to symfony 4.2.4
* Add sudo support to checkpassword script (Fixes: #127)
* Update SecurityController to use AuthenticationUtils
* Add CSRF protection to login forms (Fixes: #95)

# 2.0.2 (2019.03.06)

* Add column and filter for `hasRecoveryToken` property on user in admin list (Fixes: #144)
* Export number of users with Recovery Tokens to Munin
* Recovery also works now with email localpart (Fixes: #148)
* Fix release tar balls (Fixes: #150)

# 2.0.1 (2019.03.04)

* We adopted the code of conduct from Contributor Covenant
* Fix bug in `CryptoSecretHandler::decrypt()` that broke recovery token recreation.

# 2.0.0 (2019.02.23)

* Rename project to Userli (Fixes: #133)
* Add support for Dovecot's MailCrypt plugin. New users automatically get
  a MailCrypt key pair generated which is then passed to Dovecot via
  `checkpassword`. (Fixes: #83)
* Add support for recovery tokens. New users automatically get a recovery
  token generated, which can be used to restore their account if the
  password got lost at a later time. (Fixes: #89, #106, #108)
* Add support for direct registration links with prefilled invite vouchers.
  (Fixes: #117)
* Move flash messages to upper right corner (Fixes: #129)
* Always display footer when logged in (Fixes: #104)
* Open external links in new window (Fixes: #100)
* Add option to copy link as URL (Fixes: #117)
* Explain purpose of alias addresses (Fixes: #45)
* Remove trailing slash from default URLs
* Adjust database to snake_case. See `UPGRADE.md` on how to adjust an older
  database. (Fixes: #112)
* Add infobox with password policy to password change forms (Fixes: #130)
* Turn autocompletion off for voucher form field at registration (Fixes: #32)
* Started external docs at [systemli.github.io/userli](https://systemli.github.io/userli)

# 1.6.2 (2019.02.08)

* Update to symfony 4.1.11
* Hide vouchers in secondary domains (Fixes: #110)
* DomainAdmins are only allowed to create Alias in their domain (Fixes: #94)

# 1.6.1 (2019.01.14)

* Update to symfony 4.1.10
* Add quota to checkpassword (Fixes: #91)

# 1.6.0 (2019.01.04)

* Add a role for detected spammers (Fixes: #77)
* Split startpage into subpages (Fixes: #43)
* Reverse order of vouchers, display newest vouchers first
* Fix when users updatedTime is updated (Fixes: #71)
* Don't show voucher menu to suspicious users (Fixes: #81)

# 1.5.3 (2018.12.13)

* Add scripts to automate building and publishing of releases

# 1.5.2 (2018.12.07)

* Start to list relevant changes in a dedicated changelog file.
* Hide voucher stats from domain admins (Fixes: #72)
* Improve message about custom alias limit (Fixes: #74)

# 1.5.1 (2018.11.28)

* Fix passing passwords that start with special chars to checkpassword script
