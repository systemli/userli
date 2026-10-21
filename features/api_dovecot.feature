@api @dovecot
Feature: Dovecot API
    As a Dovecot mail server
    I need to authenticate users and lookup mailbox info
    So that I can deliver and retrieve mail

    Background:
        Given the database is clean
        And the following Domain exists:
            | name        |
            | example.org |
        And the following User exists:
            | email                 | password | roles     | mailCrypt | mailCryptPublicKey | mailCryptSecretBox |
            | user@example.org      | password | ROLE_USER | 0         |                    |                    |
            | spam@example.org      | password | ROLE_SPAM | 0         |                    |                    |
            | support@example.org   | password | ROLE_USER | 0         |                    |                    |
            | mailcrypt@example.org | password | ROLE_USER | 1         |                    |                    |
        And the following User exists:
            | email            | password | roles     | totpConfirmed | totpSecret                       |
            | totp@example.org | password | ROLE_USER | 1             | GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ |
        And the following ApiToken exists:
            | token            | name          | scopes  |
            | dovecot-test-123 | Dovecot Token | dovecot |

    @status
    Scenario: Check status endpoint
        Given I have a valid API token "dovecot-test-123"
        When I send a GET request to "/api/dovecot/status"
        Then the response status code should equal 200

    @status
    Scenario: Status endpoint with wrong token
        Given I have an invalid API token
        When I send a GET request to "/api/dovecot/status"
        Then the response status code should equal 401

    @passdb
    Scenario: Passdb lookup with correct password
        Given I have a valid API token "dovecot-test-123"
        When I send a POST request to "/api/dovecot/support@example.org" with form data:
            | password | password |
        Then the response status code should equal 200

    @passdb
    Scenario: Passdb lookup with wrong password
        Given I have a valid API token "dovecot-test-123"
        When I send a POST request to "/api/dovecot/support@example.org" with form data:
            | password | wrong |
        Then the response status code should equal 401

    @passdb @app-password
    Scenario: Passdb lookup with app password
        Given the following AppPassword exists:
            | email               | name        | password     |
            | support@example.org | Thunderbird | app-password |
        And I have a valid API token "dovecot-test-123"
        When I send a POST request to "/api/dovecot/support@example.org" with form data:
            | password | app-password |
        Then the response status code should equal 200

    @passdb @app-password @last-login
    Scenario: Passdb lookup with app password updates last login of user and app password
        Given the following AppPassword exists:
            | email               | name        | password     |
            | support@example.org | Thunderbird | app-password |
        And I have a valid API token "dovecot-test-123"
        When I send a POST request to "/api/dovecot/support@example.org" with form data:
            | password | app-password |
        Then the response status code should equal 200
        And the user "support@example.org" should have a last login time of this week
        And the app password "Thunderbird" of "support@example.org" should have a last login time of this week

    @passdb @app-password @last-login
    Scenario: Passdb lookup with password only updates last login of user
        Given the following AppPassword exists:
            | email               | name        | password     |
            | support@example.org | Thunderbird | app-password |
        And I have a valid API token "dovecot-test-123"
        When I send a POST request to "/api/dovecot/support@example.org" with form data:
            | password | password |
        Then the response status code should equal 200
        And the user "support@example.org" should have a last login time of this week
        And the app password "Thunderbird" of "support@example.org" should have no last login time

    @passdb @app-password
    Scenario: Passdb lookup with another user's app password
        Given the following AppPassword exists:
            | email            | name        | password     |
            | user@example.org | Thunderbird | app-password |
        And I have a valid API token "dovecot-test-123"
        When I send a POST request to "/api/dovecot/support@example.org" with form data:
            | password | app-password |
        Then the response status code should equal 401

    @passdb @app-password
    Scenario: Passdb lookup with app password returns MailCrypt key
        Given the following AppPassword exists:
            | email                 | name        | password     |
            | mailcrypt@example.org | Thunderbird | app-password |
        And I have a valid API token "dovecot-test-123"
        When I send a POST request to "/api/dovecot/mailcrypt@example.org" with form data:
            | password | app-password |
        Then the response status code should equal 200
        And the JSON path "body.mailCrypt" should equal "2"
        And the JSON path "body.mailCryptPrivateKey" should not be empty
        And the JSON path "body.mailCryptPublicKey" should not be empty

    @passdb @app-password
    Scenario: Passdb lookup with expired webmail credential
        Given the following AppPassword exists:
            | email               | name    | password     | expiresAt |
            | support@example.org | Webmail | app-password | -1 minute |
        And I have a valid API token "dovecot-test-123"
        When I send a POST request to "/api/dovecot/support@example.org" with form data:
            | password | app-password |
        Then the response status code should equal 401

    @passdb @twofactor
    Scenario: Passdb lookup with main password of 2FA user is rejected
        Given the setting "twofactor_app_passwords_required" is "1"
        And I have a valid API token "dovecot-test-123"
        When I send a POST request to "/api/dovecot/totp@example.org" with form data:
            | password | password |
        Then the response status code should equal 401

    @passdb @twofactor
    Scenario: Passdb lookup with main password of 2FA user when app passwords are not required
        Given the setting "twofactor_app_passwords_required" is "0"
        And I have a valid API token "dovecot-test-123"
        When I send a POST request to "/api/dovecot/totp@example.org" with form data:
            | password | password |
        Then the response status code should equal 200

    @passdb @twofactor @app-password
    Scenario: Passdb lookup with app password of 2FA user
        Given the following AppPassword exists:
            | email            | name        | password     |
            | totp@example.org | Thunderbird | app-password |
        And I have a valid API token "dovecot-test-123"
        When I send a POST request to "/api/dovecot/totp@example.org" with form data:
            | password | app-password |
        Then the response status code should equal 200

    @passdb
    Scenario: Passdb lookup for nonexistent user
        Given I have a valid API token "dovecot-test-123"
        When I send a POST request to "/api/dovecot/nonexistent@example.org" with form data:
            | password | password |
        Then the response status code should equal 404

    @passdb
    Scenario: Passdb lookup for spam user is forbidden
        Given I have a valid API token "dovecot-test-123"
        When I send a POST request to "/api/dovecot/spam@example.org" with form data:
            | password | password |
        Then the response status code should equal 403

    @userdb
    Scenario: Userdb lookup for existing user
        Given I have a valid API token "dovecot-test-123"
        When I send a GET request to "/api/dovecot/user@example.org"
        Then the response status code should equal 200
        And the JSON path "message" should equal "success"
        And the JSON path "body.user" should equal "user@example.org"
        And the JSON path "body.mailCrypt" should equal "0"

    @userdb
    Scenario: Userdb lookup for nonexistent user
        Given I have a valid API token "dovecot-test-123"
        When I send a GET request to "/api/dovecot/nonexistent@example.org"
        Then the response status code should equal 404

    @userdb
    Scenario: Userdb lookup for spam user succeeds (only passdb blocked)
        Given I have a valid API token "dovecot-test-123"
        When I send a GET request to "/api/dovecot/spam@example.org"
        Then the response status code should equal 200
