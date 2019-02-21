# Upgrade documentation

## Upgrade from 1.6.3 or lower

* Database schema changed and needs to be updated:

    bin/console doctrine:schema:update --force

* Dotenv variable `SEND_WELCOME_MAIL` was renamed to `SEND_MAIL`:

    sed -i -e 's/SEND_WELCOME_MAIL/SEND_MAIL/g' .env

* Trailing slashes have been removed from default URLs:

    sed -e 's#^\(APP_URL=".*\)/"#\1#g' .env
    sed -e 's#^\(PROJECT_URL=".*\)/"#\1#g' .env
