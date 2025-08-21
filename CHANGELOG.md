# Changelog

## 4.2.0 (2025.08.21)

### Features and Improvements

- ✨ Send email notification when a user has compromised password
- 🔒️ Obfuscate last login time

### Database Changes

- Add `user_notification` table to store user notifications

```sql
CREATE TABLE user_notification
(
    id            INT AUTO_INCREMENT NOT NULL,
    user_id       INT         NOT NULL,
    type          VARCHAR(50) NOT NULL,
    creation_time DATETIME    NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    metadata      JSON DEFAULT NULL COMMENT '(DC2Type:json)',
    INDEX         IDX_3F980AC8A76ED395 (user_id),
    INDEX         idx_user_type_creation_time (user_id, type, creation_time),
    INDEX         idx_user_type (user_id, type),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

ALTER TABLE user_notification
    ADD CONSTRAINT FK_3F980AC8A76ED395 FOREIGN KEY (user_id) REFERENCES virtual_users (id) ON DELETE CASCADE;
```

## 4.1.1 (2025.08.11)

- ♻️ Recognize JSON Requests and return JSON responses
- 🐛 Improve JSON Decoding for Dovecot Lua Auth Script

## 4.1.0 (2025.07.21)

### Features and Improvements

- 💄 Rework the UI using Tailwind CSS and Heroicons
- 🚸 Show 2FA secret during initial configuration
- 📝 Add Copilot Instructions

### Technical Changes

- ⬆️ Upgrade Symfony and related dependencies
- ➖ Removed Bootstrap 3 and KnpMenuBundle
- 🧑‍💻 Add support for remote debugging

## 4.0.0 (2025.04.12)

- Remove UsersCheckPasswordCommand (**breaking change**)
- Call webhook URLS on user created/deleted evens
- Update README
- Document how to test Dovecot integration
- Userli-dovecot-adapter: Improve error logging
- docker-compose: fix env vars
- Move docker files into own directory
- Add YAML, Markdown & Makefile to editorconfig
- Remove obsolete ansible requirements.yml
- Refactor: move user restore logic into its own handler
- refactor: Add abstract UsersBaseCommand class
- Don't allow to reset a deleted user
- Add command to restore a deleted user
- Rename MailCrypt trait to MailCryptEnabled
- Update Makefile and documentation
- Reset two-factor authentication with recovery process
- Change booleans in docker compose env vars to integers
- chore(deps): bump sonarsource/sonarqube-scan-action from 5.0.0 to 5.1.0
- Add editorconfig
- chore(deps-dev): bump the npm-dependencies group across 1 directory with 3 updates
- VoucherCountCommand: Show both used and unused vouchers per default
- fix: Enable mail_crypt for new users via admin backend
- Dockerfile: Install gpg
- Update and restructure documentation
- Suppress deprecation warnings in 'fingers_crossed' log handler
- Monolog: Fix config
- Monolog: Log to syslog in prod env
- Update GHA Runner to Ubuntu 24.04 (#736)
- Makefile: Revert change done in #727
- Docs: Fix broken links
- Docs: How to docker/podman with SELinux
- Docker compose: fix paths
- Update dependencies
- Fix typo in yarn package name on Debian/Ubuntu
- Update to symfony 6.4.19
- chore(deps-dev): bump the npm-dependencies group across 1 directory with 4 updates
- Reintroduce make integration
- Replace deprecated sonarcloud action
- fix: Link to correct installation page
- fix: Point to correct index.md
- fix: Install correct Debian packages

## 3.12.1 (2025.02.28)

- Fix Makefile

## 3.12.0 (2025.02.28)

- Release Userli-Dovecot-Adapter as own tarball
- Remove container variable in Dovecot Dockerfile
- Say goodbye to Vagrant
- Lua script: fix typo
- Simplify Command and Repository Method
- Deprecate Munin
- Add command to get voucher count for User
- Upgrade Symfony
- Update npm dependencies
- Use sqlite DB in release process to not depend on MySQL
- Various improvements to documentation

## 3.11.1 (2024.12.27)

- Fix displaying QR code when configuring 2FA
- Dovecot lua auth: Fix encoding of payload
- Dovecot lua auth: Fix quota attribute
- Add docker image for human testing
- Unify API access token handlers
- Simplify apache config example
- Improve documentation
- Update dependencies

## 3.11.0 (2024.11.30)

- Add retention API to update last login time for users
- Add retention API to list deleted users
- Do not filter displayed aliases in /alias
- Support trusted proxies for userli behind reverse proxy
- Fix postfix controller
- Fix userdb attributes in dovecot lua auth script
- Migrate large parts of release process to Makefile
- Add Deprecation for UsersCheckPasswordCommand
- Update dependencies

## 3.10.0 (2024.11.10)

- Upgrade to Symfony 6.4.14 (#659)
- Add API for Postfix (#644)
- Add API for Dovecot (#651)

## 3.9.1 (2024.09.29)

- Fix adding Users in Admin
- Show email and domain filters per default in admin user list

## 3.9.0 (2024.09.27)

- Add roundcube API endpoint to get list of aliases
- Fix MailCryptKeyHandler create/update (#629)
- Update dependencies

## 3.8.0 (2024.05.30)

- Add Support for TOTP in KeyCloak API
- Update dependencies

## 3.7.1 (2024.05.24)

- Fix Readonly Attribute in Registration Form

## 3.7.0 (2024.05.20)

- Move validation config from yaml to attributes
- Adjust Validator Signatures
- Add Types to Properties
- Split StartController into specific Controllers
- Vagrant provisioning: Install php-sqlite3
- PasswordChange: Use builtin validators
- Gather Coverage from Behat Tests
- Add TestCase for DomainCreator
- Make password not optional in PasswordUpdater
- Add keycloak API endpoints
- Update dependencies

## 3.6.0 (2024.04.15)

- Remove URL-based localized routes, store locale in session
- Check for invited by `ROLE_SUSPICIOUS` when assigning the role
- Don't accept invite codes of suspicious users on registration
- Add missing use to RecoveryController
- Add missing default value for roles column
- Require Node v18.x
- Check whether user is suspicious before creating voucher
- Bring back old logic of `findOneBySource()` in AliasRepository
- Update dependencies

## 3.5.2 (2024.03.28)

- Add fallback route for `/recovery`

## 3.5.1 (2024.03.24)

- Migrate from deprecated `$defaultName` to name annotation
- Update docs how to test `checkpassword` command

## 3.5.0 (2024.03.24)

- Fix CSP Settings for Sonata Admin
- Fix malformed expiry date for PGP key
- Use mkdocs instead of hugo
- Improve documentation
- Migrate Doctrine mappings to PHP annotations
- Make Project PHP 8 ready
- Move routing configuration to annotations
- Modernize form login
- Fix regression in CRUD controllers
- Modernize authentication and repositories
- Fix malformed date for recovery page
- Fix login for deleted user in UsersCheckPasswordCommand
- Update dependencies
- Upgrade to Symfony 6
- Split index route in public (index) and authenticated (start)

## 3.4.0 (2024.01.05)

- Adjust Admin List Order
- Add __toString methods to Entities
- Improve fixture loading while increasing the number of fixtures
- Fix Filters in User Admin
- Fix Filters in Alias Admin
- Improve Performance for Alias and Voucher Admin
- Use Autocomplete for loading Users in Alias and Voucher Admin

## 3.3.1 (2023.11.12)

- Update dependencies

## 3.3.0 (2023.11.08)

- Add command to delete a user alias.
- Fix setting last_login_time on authentication through checkpassword command.

## 3.2.3 (2023.10.31)

- Update dependencies

## 3.2.2 (2023.10.10)

- Update dependencies

## 3.2.1 (2023.04.22)

- Fix display of recovery token during registration process (Fixes #451)
- Update dependencies

## 3.2.0 (2023.03.30)

- Add Command to export metrics to Prometheus

## 3.1.0 (2022.10.26)

- Add Two-factor authentication support

## 3.0.0 (2022.06.29)

- Drop official support for PHP 7.3
- Update to symfony 4.4.40
- Update dependencies
- Update translations
- Rework Registration Config (removed `HAS_SINA_BOX`, added `REGISTRATION_OPEN`)

## 2.9.0 (2022.03.03)

- Add Italian as supported language (Thanks J. Lavoie)
- Update to symfony 4.4.38
- Update dependencies

## 2.8.0 (2022.01.28)

- Add console command to reset a user (`app:users:reset`)
- Update dependencies
- Many code style fixes

## 2.7.18 (2022.01.10)

- Fix CheckPasswordCommand with latest symfony/process (Fixes #341)
- Document cron job to delete obsolete maildirs. Thanks to 1resu.

## 2.7.17 (2021.12.30)

- Set creationTime and updatedTime in all entity constructors (Fixes #207)
- Update to symfony 4.3.36
- Update dependencies

## 2.7.16 (2021.11.03)

- Show correct random alias by forcing reload (Fixes #307)
- Update German and Frensh translations
- Update to symfony 4.3.33
- Update dependencies

## 2.7.15 (2021.08.06)

- Show correct random alias without reload (Fixes #307)
- Make php7.3 serve traffic in vagrant
- Update PHP and JS dependencies
- Rename default git branch to `main`
- Include default translations

## 2.7.14 (2021.08.04)

- Don't print info line in RemoveUsersCommand with `--list`

## 2.7.13 (2021.08.04)

- Add `--list` option to RemoveUsersCommand to list maildir directories
- Add psalm static analysis CI job
- Add contributors to README.md

## 2.7.12 (2021.06.25)

- Fix query logic when listing inactive users in findInactiveUsers()

## 2.7.11 (2021.06.25)

- Update PHP and JS dependencies
- Add ROLE_PERMANANT to be used for excluding accounts in user cleanup
- Update ansible roles to fix playbook run
- Add console command `app:users:list` with option to list inactive users
- Dispatch AliasCreatedEvent after validation (Fixes: #216)

## 2.7.10 (2021.05.20)

- Really fix CVE-2021-21424
- Upgrade to symfony 4.4.24
- Update PHP and JS dependencies

## 2.7.9 (2021.05.14)

- Fix CVE-2021-21424
- Upgrade to symfony 4.4.23
- Update PHP and JS dependencies

## 2.7.8 (2021.03.08)

- Typo fixes
- Update dependencies
- Add placeholder for recovery code (Thanks xshadow)

## 2.7.7 (2020.11.25)

- Limit permissions to set admin role to admin users
- Move mail location and dovecot UID/GID settings to environment
  variables (Thanks 1resu)
- Show footer on recovery page (Fixes: #141, thanks 1resu)
- Add hint to clear the cache to docs (Thanks 1resu)
- Open homepage link in same window (Thanks 1resu)
- Change username input type in login forms (Thanks 1resu)

## 2.7.6 (2020.11.16)

- Checkpassword: Don't throw an Exception on missing password

## 2.7.5 (2020.11.10)

- Always initiate Web Key Directory for new domains
- Improve style of pasted OpenPGP key (Thanks trashcan55)

## 2.7.4 (2020.10.27)

- Fix verification of invite codes

## 2.7.3 (2020.10.21)

- Improve OpenPGP key import filter:
  - Keep UIDs with valid email address but without realname
  - Drop UIDs with invalid email address that have the valid email
    address in realname

## 2.7.2 (2020.10.21)

- Fix Munin account stats
- Fix overwriting existing OpenPGP keys
- Upgrade Travis CI for bionic and PHP7.3

## 2.7.1 (2020.10.20)

- Downgrade `twbs/bootstrap` back to 3.3

## 2.7.0 (2020.10.20)

- Add support to import OpenPGP keys and export them to an
  OpenPGP Web Key Directory.
- Drop support for PHP 7.1 and 7.2
- Allow to batch delete users (Fixes: #78)
- Update Portugues translation (Thanks Silvério Santos)
- Update dependencies

## 2.6.1 (2020.09.10)

- Allow mails to be delivered to suspected spammers (Fixes: #212)
- Update to symfony 4.4
- Update translations

## 2.6.0 (2020.05.12)

- Attempt to fix mail body translation string format (Fixes: #205)
- Add new console command `app:users:delete` to delete users.

## 2.5.1 (2020.01.09)

- Fix userDB lookups for accounts without mail_crypt when MAIL_CRYPT=3.
- Add French translation (Thanks Nathan Bonnemains)

## 2.5.0 (2020.01.05)

- Merge `MAIL_CRYPT_*` dotenv variables into a single one. Please
  see `UPGRADING.md` and the documentation for further information.
- Update to symfony 4.3.9
- Update Norwegian translation (Thanks Alan Nordhøy)
- Allow to create first domain and account via web frontend (Fixes: #195)
- Automatically create `postmaster@domain` for all domains (Fixes: #111)

## 2.4.0 (2019.11.18)

- Skip check against HIBP service if unavailable
- Add copy-to-clipboard for aliases (Fixes: #181) (Thanks @jelhan)
- Add Swiss German to supported languages (Thanks wee)
- Update dependencies

## 2.3.0 (2019.07.16)

- Add manual language switcher (Fixes: #172)
- Add Norwegian Bokmål as available translation
- Block Spammers from authenticating via checkpassword (Fixes: #177)
- Test passwords againt haveibeenpwned database (Fixes: #161)
- Upgrade to symfony 4.3.2
- Improve speed of Vagrant box

## 2.2.3 (2019.06.24)

- Repair js copying of invite codes (Fixes: #165)
- Several minor language fixes (Thanks to ssantos)
- Start Norwegian translation (Thanks to Allan Nordhøy)
- Switch to PHP-only checkpassword script for security reasons. This
  eliminates the requirement to setup sudo. See the updated docs for
  details.

## 2.2.2 (2019.06.14)

- Delete aliases when deleting user (Fixes: #121)
- Fix error when trying to register deleted username (Fixes: #176)
- Remove link to registration from right navbar
- Update PHP and JS dependecies

## 2.2.1 (2019.06.06)

- Add org/organisation/... to reserved names
- Update to symfony 4.2.9
- Update PHP and JS dependecies
- Rename ROLE_SUPPORT to ROLE_MULTIPLIER

## 2.2.0 (2019.05.22)

- Add initial Spanish translation
- Add initial Portuguese translation (Thanks to Bruno Gama)
- Add plural forms of many reserved names
- Update to symfony 4.2.8
- Fix mailcrypt-encrypt-maildir script for paths with whitespaces
- Fix release tarball creation, don't use tar option --transform

## 2.1.2 (2019.04.18)

- Create release tarball in subdirectory
- Add optional link to webmail (Fixes: #146)
- Update to symfony 4.2.7

## 2.1.1 (2019.03.17)

- Change default locale setting to 'en'
- Don't resolve symlinks to not break sudo in checkpassword

## 2.1.0 (2019.03.17)

- New shell script `bin/mailcrypt-encrypt-maildir` to encrypt legacy mailboxes
- Update to symfony 4.2.4
- Add sudo support to checkpassword script (Fixes: #127)
- Update SecurityController to use AuthenticationUtils
- Add CSRF protection to login forms (Fixes: #95)

## 2.0.2 (2019.03.06)

- Add column and filter for `hasRecoveryToken` property on user in admin list (Fixes: #144)
- Export number of users with Recovery Tokens to Munin
- Recovery also works now with email localpart (Fixes: #148)
- Fix release tar balls (Fixes: #150)

## 2.0.1 (2019.03.04)

- We adopted the code of conduct from Contributor Covenant
- Fix bug in `CryptoSecretHandler::decrypt()` that broke recovery token recreation.

## 2.0.0 (2019.02.23)

- Rename project to Userli (Fixes: #133)
- Add support for Dovecot's MailCrypt plugin. New users automatically get
  a MailCrypt key pair generated which is then passed to Dovecot via
  `checkpassword`. (Fixes: #83)
- Add support for recovery tokens. New users automatically get a recovery
  token generated, which can be used to restore their account if the
  password got lost at a later time. (Fixes: #89, #106, #108)
- Add support for direct registration links with prefilled invite vouchers.
  (Fixes: #117)
- Move flash messages to upper right corner (Fixes: #129)
- Always display footer when logged in (Fixes: #104)
- Open external links in new window (Fixes: #100)
- Add option to copy link as URL (Fixes: #117)
- Explain purpose of alias addresses (Fixes: #45)
- Remove trailing slash from default URLs
- Adjust database to snake_case. See `UPGRADE.md` on how to adjust an older
  database. (Fixes: #112)
- Add infobox with password policy to password change forms (Fixes: #130)
- Turn autocompletion off for voucher form field at registration (Fixes: #32)
- Started external docs at [systemli.github.io/userli](https://systemli.github.io/userli)

## 1.6.2 (2019.02.08)

- Update to symfony 4.1.11
- Hide vouchers in secondary domains (Fixes: #110)
- DomainAdmins are only allowed to create Alias in their domain (Fixes: #94)

## 1.6.1 (2019.01.14)

- Update to symfony 4.1.10
- Add quota to checkpassword (Fixes: #91)

## 1.6.0 (2019.01.04)

- Add a role for detected spammers (Fixes: #77)
- Split startpage into subpages (Fixes: #43)
- Reverse order of vouchers, display newest vouchers first
- Fix when users updatedTime is updated (Fixes: #71)
- Don't show voucher menu to suspicious users (Fixes: #81)

## 1.5.3 (2018.12.13)

- Add scripts to automate building and publishing of releases

## 1.5.2 (2018.12.07)

- Start to list relevant changes in a dedicated changelog file.
- Hide voucher stats from domain admins (Fixes: #72)
- Improve message about custom alias limit (Fixes: #74)

## 1.5.1 (2018.11.28)

- Fix passing passwords that start with special chars to checkpassword script
