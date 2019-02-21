# Upgrade documentation

## Upgrade from 1.6.3 or lower

* Database schema changed and needs to be updated:

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
    CHANGE redeemedTime redeemed_time DATETIME NOT NULL;
    
    ALTER TABLE virtual_reserved_names
    CHANGE creationTime creation_time DATETIME NOT NULL,
    CHANGE updatedTime updated_time DATETIME NOT NULL;
    
    ALTER TABLE virtual_domains
    CHANGE creationTime creation_time DATETIME NOT NULL,
    CHANGE updatedTime updated_time DATETIME NOT NULL;

* Dotenv variable `SEND_WELCOME_MAIL` was renamed to `SEND_MAIL`:

    sed -i -e 's/SEND_WELCOME_MAIL/SEND_MAIL/g' .env

* New mandatory Dotenv variables were added (all related to MailCrypt):

    MAIL_CRYPT_ENABLED=1
    MAIL_CRYPT_AUTO=1

* Trailing slashes have been removed from default URLs:

    sed -e 's#^\(APP_URL=".*\)/"#\1#g' .env
    sed -e 's#^\(PROJECT_URL=".*\)/"#\1#g' .env
