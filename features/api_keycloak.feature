@api @keycloak
Feature: Keycloak API
    As a Keycloak identity provider
    I need to search, validate and lookup users
    So that I can federate user identities

    Background:
        Given the database is clean
        And the following Domain exists:
            | name        |
            | example.org |
        And the following User exists:
            | email                | password | roles               | totpConfirmed | totpSecret                       | deleted | passwordChangeRequired |
            | admin@example.org    | password | ROLE_USER           | 0             |                                  | 0       | 0                      |
            | user@example.org     | password | ROLE_USER           | 0             |                                  | 0       | 0                      |
            | totp@example.org     | password | ROLE_USER           | 1             | GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ | 0       | 0                      |
            | deleted@example.org  | password | ROLE_USER           | 0             |                                  | 1       | 0                      |
            | spam@example.org     | password | ROLE_USER,ROLE_SPAM | 0             |                                  | 0       | 0                      |
            | pwchange@example.org | password | ROLE_USER           | 0             |                                  | 0       | 1                      |
            | totpdel@example.org  | password | ROLE_USER           | 1             | GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ | 1       | 0                      |
            | totpspam@example.org | password | ROLE_USER,ROLE_SPAM | 1             | GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ | 0       | 0                      |
            | totppwc@example.org  | password | ROLE_USER           | 1             | GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ | 0       | 1                      |
            | backup@example.org   | password | ROLE_USER           | 1             | GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ | 0       | 0                      |
        And the User "backup@example.org" has TOTP backup codes "111111,222222,333333"
        And the following ApiToken exists:
            | token             | name           | scopes   |
            | keycloak-test-123 | Keycloak Token | keycloak |

    @search
    Scenario: Get users search with wrong API token
        Given I have an invalid API token
        When I send a GET request to "/api/keycloak/example.org?search=example&max=2"
        Then the response status code should equal 401

    @search
    Scenario: Search users
        Given I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org?search=example&max=2"
        Then the response status code should equal 200
        And the JSON response should contain 2 items

    @search
    Scenario: Search users on nonexistent domain
        Given I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/nonexistent.org?search=example&max=2"
        Then the response status code should equal 404

    @count
    Scenario: Get users count
        Given I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/count"
        Then the response status code should equal 200
        And the JSON response should be:
            """
            8
            """

    @user
    Scenario: Get one user
        Given I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/user/user@example.org"
        Then the response status code should equal 200
        And the JSON path "id" should equal "user"
        And the JSON path "email" should equal "user@example.org"

    @user
    Scenario: Get nonexistent user
        Given I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/user/nonexistent@example.org"
        Then the response status code should equal 404

    @validate
    Scenario: Validate user with correct password
        Given I have a valid API token "keycloak-test-123"
        When I send a POST request to "/api/keycloak/example.org/validate/user@example.org" with form data:
            | credentialType | password |
            | password       | password |
        Then the response status code should equal 200
        And the JSON path "message" should equal "success"

    @validate @app-password
    Scenario: Validate user with app password is rejected
        Given the following AppPassword exists:
            | email            | name        | password     |
            | user@example.org | Thunderbird | app-password |
        And I have a valid API token "keycloak-test-123"
        When I send a POST request to "/api/keycloak/example.org/validate/user@example.org" with form data:
            | credentialType | password     |
            | password       | app-password |
        Then the response status code should equal 403
        And the JSON path "message" should equal "authentication failed"

    @validate
    Scenario: Validate user with wrong password
        Given I have a valid API token "keycloak-test-123"
        When I send a POST request to "/api/keycloak/example.org/validate/user@example.org" with form data:
            | password | wrong |
        Then the response status code should equal 403
        And the JSON path "message" should equal "authentication failed"

    @validate
    Scenario: Validate user with unsupported credential type
        Given I have a valid API token "keycloak-test-123"
        When I send a POST request to "/api/keycloak/example.org/validate/user@example.org" with form data:
            | credentialType | wrong    |
            | password       | password |
        Then the response status code should equal 400
        And the JSON path "message" should equal "not supported"

    @validate
    Scenario: Validate nonexistent user
        Given I have a valid API token "keycloak-test-123"
        When I send a POST request to "/api/keycloak/example.org/validate/404@example.org" with form data:
            | credentialType | password |
            | password       | password |
        Then the response status code should equal 403
        And the JSON path "message" should equal "authentication failed"

    @validate @otp
    Scenario: Validate OTP for user without TOTP configured
        Given I have a valid API token "keycloak-test-123"
        When I send a POST request to "/api/keycloak/example.org/validate/user@example.org" with form data:
            | credentialType | otp    |
            | password       | 123456 |
        Then the response status code should equal 403

    @configured
    Scenario: Check if OTP is configured for user without TOTP
        Given I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/configured/otp/user@example.org"
        Then the response status code should equal 404

    @configured
    Scenario: Check if OTP is configured for user with TOTP
        Given I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/configured/otp/totp@example.org"
        Then the response status code should equal 200

    @configured
    Scenario: Check if password is configured for existing user
        Given I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/configured/password/user@example.org"
        Then the response status code should equal 200

    @configured
    Scenario: Check if password is configured for nonexistent user
        Given I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/configured/password/404@example.org"
        Then the response status code should equal 404

    @validate @status-check
    Scenario: Validate deleted user with correct password is rejected
        Given I have a valid API token "keycloak-test-123"
        When I send a POST request to "/api/keycloak/example.org/validate/deleted@example.org" with form data:
            | credentialType | password |
            | password       | password |
        Then the response status code should equal 403
        And the JSON path "message" should equal "authentication failed"

    @validate @status-check
    Scenario: Validate spam user with correct password is rejected
        Given I have a valid API token "keycloak-test-123"
        When I send a POST request to "/api/keycloak/example.org/validate/spam@example.org" with form data:
            | credentialType | password |
            | password       | password |
        Then the response status code should equal 403
        And the JSON path "message" should equal "authentication failed"

    @validate @status-check
    Scenario: Validate user requiring password change with correct password is rejected
        Given I have a valid API token "keycloak-test-123"
        When I send a POST request to "/api/keycloak/example.org/validate/pwchange@example.org" with form data:
            | credentialType | password |
            | password       | password |
        Then the response status code should equal 403
        And the JSON path "message" should equal "password change required"

    @validate @otp @status-check
    Scenario: Validate OTP for deleted user is rejected
        Given I have a valid API token "keycloak-test-123"
        When I send a POST request to "/api/keycloak/example.org/validate/totpdel@example.org" with form data:
            | credentialType | otp    |
            | password       | 123456 |
        Then the response status code should equal 403
        And the JSON path "message" should equal "authentication failed"

    @validate @otp @status-check
    Scenario: Validate OTP for spam user is rejected
        Given I have a valid API token "keycloak-test-123"
        When I send a POST request to "/api/keycloak/example.org/validate/totpspam@example.org" with form data:
            | credentialType | otp    |
            | password       | 123456 |
        Then the response status code should equal 403
        And the JSON path "message" should equal "authentication failed"

    @validate @otp @status-check
    Scenario: Validate OTP for user requiring password change is rejected
        Given I have a valid API token "keycloak-test-123"
        When I send a POST request to "/api/keycloak/example.org/validate/totppwc@example.org" with form data:
            | credentialType | otp    |
            | password       | 123456 |
        Then the response status code should equal 403
        And the JSON path "message" should equal "password change required"

    @validate @otp @backup-code
    Scenario: Validate OTP with a valid backup code succeeds
        Given I have a valid API token "keycloak-test-123"
        When I send a POST request to "/api/keycloak/example.org/validate/backup@example.org" with form data:
            | credentialType | otp    |
            | password       | 111111 |
        Then the response status code should equal 200
        And the JSON path "message" should equal "success"

    @validate @otp @backup-code
    Scenario: Validate OTP with an invalid backup code is rejected
        Given I have a valid API token "keycloak-test-123"
        When I send a POST request to "/api/keycloak/example.org/validate/backup@example.org" with form data:
            | credentialType | otp    |
            | password       | 987654 |
        Then the response status code should equal 403
        And the JSON path "message" should equal "authentication failed"

    @validate @otp @backup-code
    Scenario: Backup code is consumed after successful use
        Given I have a valid API token "keycloak-test-123"
        When I send a POST request to "/api/keycloak/example.org/validate/backup@example.org" with form data:
            | credentialType | otp    |
            | password       | 222222 |
        Then the response status code should equal 200
        When I send a POST request to "/api/keycloak/example.org/validate/backup@example.org" with form data:
            | credentialType | otp    |
            | password       | 222222 |
        Then the response status code should equal 403
        And the JSON path "message" should equal "authentication failed"

    @configured @webauthn
    Scenario: Check if WebAuthn is configured for user without security key
        Given I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/configured/webauthn/user@example.org"
        Then the response status code should equal 404

    @configured @webauthn
    Scenario: Check if WebAuthn is configured for user with security key
        Given the User "user@example.org" has the security key "YubiKey"
        And I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/configured/webauthn/user@example.org"
        Then the response status code should equal 200
        And the JSON path "message" should equal "success"

    @webauthn
    Scenario: List WebAuthn credentials of a user
        Given the User "user@example.org" has the security key "YubiKey"
        And I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/credentials/webauthn/user@example.org"
        Then the response status code should equal 200
        And the JSON response should contain 1 items
        And the JSON path "0.name" should equal "YubiKey"
        And the JSON path "0.transports.0" should equal "usb"
        And the JSON path "0.credentialId" should not be empty
        And the JSON path "0.publicKey" should not be empty
        And the JSON path "0.aaguid" should equal "00000000-0000-0000-0000-000000000000"
        And the JSON path "0.attestationType" should equal "none"
        And the JSON path "0.signCount" should equal "0"

    @webauthn
    Scenario: Store the sign count of a WebAuthn credential
        Given the User "user@example.org" has the security key "YubiKey"
        And I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/credentials/webauthn/user@example.org"
        And I store the JSON path "0.id" as "keyId"
        When I send a PUT request to "/api/keycloak/example.org/credentials/webauthn/user@example.org/{keyId}" with JSON:
            """
            {"signCount": 3}
            """
        Then the response status code should equal 200
        And the JSON path "message" should equal "success"
        When I send a GET request to "/api/keycloak/example.org/credentials/webauthn/user@example.org"
        Then the JSON path "0.signCount" should equal "3"

    @webauthn
    Scenario: Sign count of a WebAuthn credential that does not increase is rejected
        Given the User "user@example.org" has the security key "YubiKey"
        And I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/credentials/webauthn/user@example.org"
        And I store the JSON path "0.id" as "keyId"
        When I send a PUT request to "/api/keycloak/example.org/credentials/webauthn/user@example.org/{keyId}" with JSON:
            """
            {"signCount": 3}
            """
        Then the response status code should equal 200
        When I send a PUT request to "/api/keycloak/example.org/credentials/webauthn/user@example.org/{keyId}" with JSON:
            """
            {"signCount": 2}
            """
        Then the response status code should equal 409
        And the JSON path "message" should equal "sign count rejected"

    @webauthn
    Scenario: Sign count of a WebAuthn credential of another user is rejected
        Given the User "admin@example.org" has the security key "YubiKey"
        And I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/credentials/webauthn/admin@example.org"
        And I store the JSON path "0.id" as "keyId"
        When I send a PUT request to "/api/keycloak/example.org/credentials/webauthn/user@example.org/{keyId}" with JSON:
            """
            {"signCount": 3}
            """
        Then the response status code should equal 404
        And the JSON path "message" should equal "not found"

    @webauthn
    Scenario: Sign count of a WebAuthn credential of a deleted user is rejected
        Given the User "deleted@example.org" has the security key "YubiKey"
        And the User "user@example.org" has the security key "YubiKey"
        And I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/credentials/webauthn/user@example.org"
        And I store the JSON path "0.id" as "keyId"
        When I send a PUT request to "/api/keycloak/example.org/credentials/webauthn/deleted@example.org/{keyId}" with JSON:
            """
            {"signCount": 3}
            """
        Then the response status code should equal 403
        And the JSON path "message" should equal "authentication failed"

    @webauthn
    Scenario: List WebAuthn credentials of a nonexistent user
        Given I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/credentials/webauthn/404@example.org"
        Then the response status code should equal 404

    @webauthn
    Scenario: List WebAuthn credentials of a deleted user
        Given the User "deleted@example.org" has the security key "YubiKey"
        And I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/credentials/webauthn/deleted@example.org"
        Then the response status code should equal 403
        And the JSON path "message" should equal "authentication failed"

    @configured @backup-code
    Scenario: Check if backup codes are configured for user with only a security key
        Given the User "user@example.org" has the security key "YubiKey"
        And the User "user@example.org" has TOTP backup codes "111111,222222"
        And I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/configured/backup-code/user@example.org"
        Then the response status code should equal 200

    @configured @backup-code
    Scenario: Backup codes are not configured for user without second factor
        Given the User "user@example.org" has TOTP backup codes "111111,222222"
        And I have a valid API token "keycloak-test-123"
        When I send a GET request to "/api/keycloak/example.org/configured/backup-code/user@example.org"
        Then the response status code should equal 404

    @validate @backup-code
    Scenario: Validate backup code for user with only a security key
        Given the User "user@example.org" has the security key "YubiKey"
        And the User "user@example.org" has TOTP backup codes "111111,222222"
        And I have a valid API token "keycloak-test-123"
        When I send a POST request to "/api/keycloak/example.org/validate/user@example.org" with form data:
            | credentialType | backup-code |
            | password       | 111111      |
        Then the response status code should equal 200
        When I send a POST request to "/api/keycloak/example.org/validate/user@example.org" with form data:
            | credentialType | backup-code |
            | password       | 111111      |
        Then the response status code should equal 403
        And the JSON path "message" should equal "authentication failed"

    @validate @backup-code
    Scenario: Backup code is rejected for user without second factor
        Given the User "user@example.org" has TOTP backup codes "111111,222222"
        And I have a valid API token "keycloak-test-123"
        When I send a POST request to "/api/keycloak/example.org/validate/user@example.org" with form data:
            | credentialType | backup-code |
            | password       | 111111      |
        Then the response status code should equal 403
        And the JSON path "message" should equal "not supported"
