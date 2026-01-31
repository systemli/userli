# Upgrade documentation

## Upgrade from 5.4.1 or lower

Starting with version 5.5.0, we switched from manual database migrations to
using Doctrine Migrations. If you are upgrading from 5.4.1 or lower, follow
these steps:

1. **First, upgrade to version 5.4.1** and apply all manual database schema
   changes documented below for your current version.

2. **Then, upgrade to the latest version** and initialize Doctrine Migrations:

```shell
bin/console doctrine:migrations:migrate --no-interaction
```

This will mark all existing migrations as executed (since you already applied
the schema changes manually) and run any new migrations.

## Upgrade from 3.1.0 or lower

Optional note field for aliases was added. Update your database schema:

    ALTER TABLE virtual_aliases
    ADD note VARCHAR DEFAULT NULL;

Userli migrated from swiftmailer-bundle to symfony/mailer. Remove environment
variable `MAILER_URL` and replace it with `MAILER_DSN`. See `.env` file for the
syntax.

## Upgrade from 3.0.0 or lower

The new twofactor authentication (2FA) feature requires the database schema to
be updated:

    ALTER TABLE virtual_users
    ADD totp_confirmed TINYINT(1) DEFAULT 0 NOT NULL,
    ADD totp_secret VARCHAR(255) DEFAULT NULL;
    ADD totp_backup_codes LONGTEXT NOT NULL;

## Upgrade from 2.6.1 or lower

The new OpenPGP WKD feature requires GnuPG (>=2.1.14) to be installed.

Database schema changed and needs to be updated:

    CREATE TABLE virtual_openpgp_keys (
      id INT AUTO_INCREMENT NOT NULL,
      user_id INT DEFAULT NULL,
      email VARCHAR(255) NOT NULL,
      key_id LONGTEXT NOT NULL,
      key_fingerprint LONGTEXT NOT NULL,
      key_expire_time DATETIME DEFAULT NULL,
      key_data LONGTEXT NOT NULL,
      UNIQUE INDEX UNIQ_3DB259EAE7927C74 (email),
      INDEX IDX_3DB259EAA76ED395 (user_id),
      PRIMARY KEY(id))
      DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
    ALTER TABLE virtual_openpgp_keys
      ADD CONSTRAINT FK_3DB259EAA76ED395 FOREIGN KEY (user_id) REFERENCES virtual_users (id);

## Upgrade from 2.4.0 or lower

The `MAIL_CRYPT_*` Dotenv variables were merged into one variable:

    MAIL_CRYPT=2

See the documentation for further information on supported values.

The `DOMAIN` Dotenv variable is now obsolete. It is replaced
by the first created domain at the moment.

## Upgrade from 2.1.1 or lower

New optional Dotenv variable was added to link to webmail:

   WEBMAIL_URL="roundcube.example.org"

## Upgrade from 1.6.3 or lower

Database schema changed and needs to be updated:

    ALTER TABLE virtual_users
    ADD recovery_secret_box LONGTEXT DEFAULT NULL,
    ADD recovery_start_time DATETIME DEFAULT NULL,
    ADD mail_crypt TINYINT(1) DEFAULT '0' NOT NULL,
    ADD mail_crypt_secret_box LONGTEXT DEFAULT NULL,
    ADD mail_crypt_public_key LONGTEXT DEFAULT NULL,
    CHANGE creationTime creation_time DATETIME NOT NULL,
    CHANGE updatedTime updated_time DATETIME NOT NULL,
    CHANGE lastlogin last_login_time DATETIME DEFAULT NULL,
    CHANGE passwordversion password_version INT NOT NULL;

    ALTER TABLE virtual_aliases
    CHANGE creationTime creation_time DATETIME NOT NULL,
    CHANGE updatedTime updated_time DATETIME NOT NULL;

    ALTER TABLE virtual_vouchers
    CHANGE creationTime creation_time DATETIME NOT NULL,
    CHANGE redeemedTime redeemed_time DATETIME DEFAULT NULL;

    ALTER TABLE virtual_reserved_names
    CHANGE creationTime creation_time DATETIME NOT NULL,
    CHANGE updatedTime updated_time DATETIME NOT NULL;

    ALTER TABLE virtual_domains
    CHANGE creationTime creation_time DATETIME NOT NULL,
    CHANGE updatedTime updated_time DATETIME NOT NULL;

Dotenv variable `SEND_WELCOME_MAIL` was renamed to `SEND_MAIL`:

    sed -i -e 's/SEND_WELCOME_MAIL/SEND_MAIL/g' .env

New mandatory Dotenv variables were added (all related to MailCrypt):

    MAIL_CRYPT_ENABLED=1
    MAIL_CRYPT_AUTO=1

Trailing slashes have been removed from default URLs:

    sed -e 's#^\(APP_URL=".*\)/"#\1#g' .env
    sed -e 's#^\(PROJECT_URL=".*\)/"#\1#g' .env

Use 0/1 instead of false/true in .env

    sed -i -e 's/="true"/=1/g' .env
    sed -i -e 's/="false"/=0/g' .env
