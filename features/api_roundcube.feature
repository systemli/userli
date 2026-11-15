@api @roundcube
Feature: Roundcube API
    As a Roundcube webmail client
    I need to retrieve user aliases after authentication
    So that users can send mail from their alias addresses

    Background:
        Given the database is clean
        And the following Domain exists:
            | name        |
            | example.org |
            | example.com |
        And the following User exists:
            | email             | password | roles     | totpConfirmed | totpSecret                       |
            | user@example.org  | password | ROLE_USER |               |                                  |
            | user2@example.org | password | ROLE_USER |               |                                  |
            | totp@example.org  | password | ROLE_USER | 1             | GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ |
        And the following User exists:
            | email            | password | roles     | passwordChangeRequired |
            | spam@example.org | password | ROLE_SPAM | 0                      |
            | pwc@example.org  | password | ROLE_USER | 1                      |
        And the following Alias exists:
            | user_id | source             | destination       |
            | 2       | alias@example.org  | user2@example.org |
            | 2       | alias2@example.org | user2@example.org |
        And the following ApiToken exists:
            | token              | name            | scopes    |
            | roundcube-test-123 | Roundcube Token | roundcube |
            | dovecot-test-123   | Dovecot Token   | dovecot   |

    @aliases
    Scenario: Get user aliases without credentials
        Given I have no API token
        When I send a POST request to "/api/roundcube/aliases"
        Then the response status code should equal 401

    @aliases
    Scenario: Get user aliases with invalid request format
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/aliases" with raw body:
            """
            {"email": "user2@example.org", "password": "password"}
            """
        Then the response status code should equal 400

    @aliases
    Scenario: Get user aliases for user with aliases
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/aliases" with form data:
            | email    | user2@example.org |
            | password | password          |
        Then the response status code should equal 200
        And the JSON response should be:
            """
            ["alias@example.org", "alias2@example.org"]
            """

    @aliases @app-password
    Scenario: Get user aliases with app password is rejected
        Given the following AppPassword exists:
            | email             | name        | password     |
            | user2@example.org | Thunderbird | app-password |
        And I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/aliases" with form data:
            | email    | user2@example.org |
            | password | app-password      |
        Then the response status code should equal 401

    @aliases
    Scenario: Get user aliases for user without aliases
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/aliases" with form data:
            | email    | user@example.org |
            | password | password         |
        Then the response status code should equal 200
        And the JSON response should be:
            """
            []
            """

    @aliases
    Scenario: Get cross-domain aliases for user
        Given the following Alias exists:
            | user_id | source                      | destination       |
            | 2       | crossdomain@example.com     | user2@example.org |
        And I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/aliases" with form data:
            | email    | user2@example.org |
            | password | password          |
        Then the response status code should equal 200
        And the JSON response should contain "alias@example.org"
        And the JSON response should contain "alias2@example.org"
        And the JSON response should contain "crossdomain@example.com"

    @login
    Scenario: Login returns a webmail credential that works in Dovecot
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | user@example.org |
            | password | password         |
        Then the response status code should equal 200
        And the JSON path "password" should not be empty
        And the JSON path "expiresAt" should not be empty
        When I store the JSON path "password" as "%webmail%"
        And I have a valid API token "dovecot-test-123"
        And I send a POST request to "/api/dovecot/user@example.org" with form data:
            | password | %webmail% |
        Then the response status code should equal 200

    @login
    Scenario: Login returns a webmail credential that carries the MailCrypt key
        Given the following User exists:
            | email                 | password | roles     | mailCrypt |
            | mailcrypt@example.org | password | ROLE_USER | 1         |
        And the following AppPassword exists:
            | email                 | name        | password     |
            | mailcrypt@example.org | Thunderbird | app-password |
        And I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | mailcrypt@example.org |
            | password | password              |
        Then the response status code should equal 200
        When I store the JSON path "password" as "%webmail%"
        And I have a valid API token "dovecot-test-123"
        And I send a POST request to "/api/dovecot/mailcrypt@example.org" with form data:
            | password | %webmail% |
        Then the response status code should equal 200
        And the JSON path "body.mailCrypt" should equal "2"
        And the JSON path "body.mailCryptPrivateKey" should not be empty

    @login
    Scenario: Login with wrong password
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | user@example.org |
            | password | wrong            |
        Then the response status code should equal 401
        And the JSON path "message" should equal "authentication failed"

    @login @app-password
    Scenario: Login with app password is rejected
        Given the following AppPassword exists:
            | email            | name        | password     |
            | user@example.org | Thunderbird | app-password |
        And I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | user@example.org |
            | password | app-password     |
        Then the response status code should equal 401
        And the JSON path "message" should equal "authentication failed"

    @login @twofactor
    Scenario: Login without code for user with 2FA
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | totp@example.org |
            | password | password         |
        Then the response status code should equal 401
        And the JSON path "message" should equal "twofactor required"
        And the user "totp@example.org" should have no last login time

    @login @twofactor
    Scenario: Login with wrong code for user with 2FA
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | totp@example.org |
            | password | password         |
            | code     | 000000           |
        Then the response status code should equal 401
        And the JSON path "message" should equal "authentication failed"
        And the user "totp@example.org" should have no last login time

    @login @twofactor
    Scenario: Login with TOTP code for user with 2FA
        Given I store the TOTP code for secret "GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ" as "%code%"
        And I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | totp@example.org |
            | password | password         |
            | code     | %code%           |
        Then the response status code should equal 200
        And the JSON path "password" should not be empty
        And the user "totp@example.org" should have a last login time of this week

    @login @twofactor
    Scenario: Login with backup code works only once
        Given the User "totp@example.org" has TOTP backup codes "11111111,22222222"
        And I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | totp@example.org |
            | password | password         |
            | code     | 11111111         |
        Then the response status code should equal 200
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | totp@example.org |
            | password | password         |
            | code     | 11111111         |
        Then the response status code should equal 401

    @login
    Scenario: Login of spam user succeeds
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | spam@example.org |
            | password | password         |
        Then the response status code should equal 200
        And the JSON path "password" should not be empty

    @login
    Scenario: Login of user who must change the password is rejected
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | pwc@example.org |
            | password | password        |
        Then the response status code should equal 403
        And the JSON path "message" should equal "user password change required"

    @login
    Scenario: Login of user who must change the password with wrong password reveals nothing
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | pwc@example.org |
            | password | wrong           |
        Then the response status code should equal 401
        And the JSON path "message" should equal "authentication failed"

    @logout
    Scenario: Logout revokes the webmail credential
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | user@example.org |
            | password | password         |
        And I store the JSON path "password" as "%webmail%"
        And I send a POST request to "/api/roundcube/logout" with form data:
            | email    | user@example.org |
            | password | %webmail%        |
        Then the response status code should equal 204
        When I have a valid API token "dovecot-test-123"
        And I send a POST request to "/api/dovecot/user@example.org" with form data:
            | password | %webmail% |
        Then the response status code should equal 401

    @logout @app-password
    Scenario: Logout does not revoke app passwords
        Given the following AppPassword exists:
            | email            | name        | password     |
            | user@example.org | Thunderbird | app-password |
        And I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/logout" with form data:
            | email    | user@example.org |
            | password | app-password     |
        Then the response status code should equal 204
        When I have a valid API token "dovecot-test-123"
        And I send a POST request to "/api/dovecot/user@example.org" with form data:
            | password | app-password |
        Then the response status code should equal 200

    @login @twofactor @webauthn
    Scenario: Login without code for user with only a security key
        Given the User "user@example.org" has the security key "YubiKey"
        And I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | user@example.org |
            | password | password         |
        Then the response status code should equal 401
        And the JSON path "message" should equal "twofactor required"
        And the JSON path "webauthn.challenge" should not be empty
        And the JSON path "webauthn.allowCredentials.0.id" should not be empty

    @login @twofactor @webauthn
    Scenario: Login with wrong password does not reveal the security keys
        Given the User "user@example.org" has the security key "YubiKey"
        And I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | user@example.org |
            | password | wrong            |
        Then the response status code should equal 401
        And the JSON path "message" should equal "authentication failed"
        And the JSON path "webauthn" should be empty

    @login @twofactor @webauthn
    Scenario: Login with a security key
        Given the setting "webauthn_rp_id" is "example.org"
        And the setting "webauthn_origins" is "https://mail.example.org"
        And the User "user@example.org" has the security key "YubiKey"
        And I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | user@example.org |
            | password | password         |
        And I store the JSON path "webauthn.challenge" as "challenge"
        And I store a security key assertion of "user@example.org" for RP ID "example.org" on origin "https://mail.example.org" with challenge "challenge" as "assertion"
        And I send a POST request to "/api/roundcube/login" with form data:
            | email                        | user@example.org            |
            | password                     | password                    |
            | webauthn[challenge]          | assertion.challenge         |
            | webauthn[credentialId]       | assertion.credentialId      |
            | webauthn[clientDataJSON]     | assertion.clientDataJSON    |
            | webauthn[authenticatorData]  | assertion.authenticatorData |
            | webauthn[signature]          | assertion.signature         |
        Then the response status code should equal 200
        And the JSON path "password" should not be empty
        When I send a POST request to "/api/roundcube/login" with form data:
            | email                        | user@example.org            |
            | password                     | password                    |
            | webauthn[challenge]          | assertion.challenge         |
            | webauthn[credentialId]       | assertion.credentialId      |
            | webauthn[clientDataJSON]     | assertion.clientDataJSON    |
            | webauthn[authenticatorData]  | assertion.authenticatorData |
            | webauthn[signature]          | assertion.signature         |
        Then the response status code should equal 401
        And the JSON path "message" should equal "authentication failed"
        And the JSON path "webauthn.challenge" should not be empty

    @login @twofactor @webauthn
    Scenario: Login with a security key from an unknown origin is rejected
        Given the setting "webauthn_rp_id" is "example.org"
        And the User "user@example.org" has the security key "YubiKey"
        And I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | user@example.org |
            | password | password         |
        And I store the JSON path "webauthn.challenge" as "challenge"
        And I store a security key assertion of "user@example.org" for RP ID "example.org" on origin "https://mail.example.org" with challenge "challenge" as "assertion"
        And I send a POST request to "/api/roundcube/login" with form data:
            | email                        | user@example.org            |
            | password                     | password                    |
            | webauthn[challenge]          | assertion.challenge         |
            | webauthn[credentialId]       | assertion.credentialId      |
            | webauthn[clientDataJSON]     | assertion.clientDataJSON    |
            | webauthn[authenticatorData]  | assertion.authenticatorData |
            | webauthn[signature]          | assertion.signature         |
        Then the response status code should equal 401
        And the JSON path "message" should equal "authentication failed"

    @login @twofactor @webauthn
    Scenario: Login with a security key for user with only TOTP is rejected
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email                        | totp@example.org |
            | password                     | password         |
            | webauthn[challenge]          | AAAA             |
            | webauthn[credentialId]       | AAAA             |
            | webauthn[clientDataJSON]     | AAAA             |
            | webauthn[authenticatorData]  | AAAA             |
            | webauthn[signature]          | AAAA             |
        Then the response status code should equal 401
        And the JSON path "message" should equal "authentication failed"
        And the JSON path "webauthn" should be empty

    @login @twofactor @webauthn
    Scenario: Login with backup code for user with only a security key
        Given the User "user@example.org" has the security key "YubiKey"
        And the User "user@example.org" has TOTP backup codes "111111,222222"
        And I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | user@example.org |
            | password | password         |
            | code     | 111111           |
        Then the response status code should equal 200
        And the JSON path "password" should not be empty

    @login @twofactor @webauthn
    Scenario: Login with a TOTP-like code for user with only a security key is rejected
        Given the User "user@example.org" has the security key "YubiKey"
        And I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | user@example.org |
            | password | password         |
            | code     | 123456           |
        Then the response status code should equal 401
        And the JSON path "message" should equal "authentication failed"
