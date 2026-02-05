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
            | email             | password | roles     | totpConfirmed | totpSecret                       |
            | admin@example.org | password | ROLE_USER | 0             |                                  |
            | user@example.org  | password | ROLE_USER | 0             |                                  |
            | totp@example.org  | password | ROLE_USER | 1             | GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ |
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
            3
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
