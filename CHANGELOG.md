# 2.1.0 (unreleased)

* Update to symfony 4.2.4

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
