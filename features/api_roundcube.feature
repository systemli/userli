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

    @login @twofactor
    Scenario: Login with wrong code for user with 2FA
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | totp@example.org |
            | password | password         |
            | code     | 000000           |
        Then the response status code should equal 401
        And the JSON path "message" should equal "authentication failed"

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
    Scenario: Login of spam user is rejected
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | spam@example.org |
            | password | password         |
        Then the response status code should equal 403
        And the JSON path "message" should equal "user disabled due to spam role"

    @login
    Scenario: Login of user who must change the password is rejected
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | pwc@example.org |
            | password | password        |
        Then the response status code should equal 403
        And the JSON path "message" should equal "user password change required"

    @login @rate-limit
    Scenario: Too many login attempts are rejected, even with the right password
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/login" with form data:
            | email    | totp@example.org |
            | password | password         |
            | code     | 000000           |
        And I send a POST request to "/api/roundcube/login" with form data:
            | email    | totp@example.org |
            | password | password         |
            | code     | 000001           |
        And I send a POST request to "/api/roundcube/login" with form data:
            | email    | totp@example.org |
            | password | wrong            |
        And I store the TOTP code for secret "GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ" as "%code%"
        And I send a POST request to "/api/roundcube/login" with form data:
            | email    | totp@example.org |
            | password | password         |
            | code     | %code%           |
        Then the response status code should equal 429
        And the JSON path "message" should equal "too many attempts"

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
