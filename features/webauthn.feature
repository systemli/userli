@webauthn
Feature: Security keys
    As a user
    I want to manage my security keys
    So that I can use them as second factor

    Background:
        Given the database is clean
        And the following Domain exists:
            | name        |
            | example.org |
        And the following User exists:
            | email             | password | roles     |
            | user@example.org  | asdasd   | ROLE_USER |
            | other@example.org | asdasd   | ROLE_USER |

    Scenario: User without security keys sees the empty list
        When I am authenticated as "user@example.org"
        And I am on "/account/webauthn"
        Then the response status code should be 200
        And I should see "Add security key"
        And I should see "You have no security keys yet."

    Scenario: User sees their security keys
        Given the User "user@example.org" has the security key "YubiKey"
        When I am authenticated as "user@example.org"
        And I am on "/account/webauthn"
        Then I should see "YubiKey"
        And I should see "Never used"
        And I should not see "You have no security keys yet."

    Scenario: Adding a security key without a name fails
        When I am authenticated as "user@example.org"
        And I am on "/account/webauthn"
        And I fill in "webauthn_credential[password]" with "asdasd"
        And I press "Add security key"
        Then the response status code should be 422
        And I should see "This value should not be blank."

    Scenario: Adding a security key without a browser response fails
        When I am authenticated as "user@example.org"
        And I am on "/account/webauthn"
        And I fill in "webauthn_credential[name]" with "YubiKey"
        And I fill in "webauthn_credential[password]" with "asdasd"
        And I press "Add security key"
        Then I should see "The security key could not be registered. Please try again."
        And the User "user@example.org" should have 0 security keys

    Scenario: Removing a security key needs the password
        Given the User "user@example.org" has the security key "YubiKey"
        When I am authenticated as "user@example.org"
        And I am on "/account/webauthn"
        And I confirm with password "wrong-password" at "/account/webauthn/{YubiKey}/delete"
        Then I should be on "/account/webauthn"
        And I should see "The password you entered is incorrect."
        And the User "user@example.org" should have 1 security keys

    Scenario: Removing the last security key removes the backup codes
        Given the User "user@example.org" has the security key "YubiKey"
        And the User "user@example.org" has TOTP backup codes "111111,222222"
        When I am authenticated as "user@example.org"
        And I am on "/account/webauthn"
        And I confirm with password "asdasd" at "/account/webauthn/{YubiKey}/delete"
        Then I should be on "/account/webauthn"
        And I should see "The security key was removed."
        And the User "user@example.org" should have 0 security keys
        When I am on "/account/twofactor"
        Then I should see "Two-factor authentication is not active"
        And I should see "Created when you set up your first second factor."

    Scenario: User cannot remove the security key of another user
        Given the User "other@example.org" has the security key "OtherKey"
        When I am authenticated as "user@example.org"
        And I am on "/account/webauthn"
        And I confirm with password "asdasd" at "/account/webauthn/{OtherKey}/delete"
        Then the response status code should be 404
        And the User "other@example.org" should have 1 security keys

    Scenario: Overview warns when few backup codes are left
        Given the User "user@example.org" has the security key "YubiKey"
        And the User "user@example.org" has TOTP backup codes "111111"
        When I am authenticated as "user@example.org"
        And I am on "/account/twofactor"
        Then I should see "One code left"
        And I should see an "[data-twofactor-method=backup-codes] .bg-yellow-100" element

    Scenario: Login asks for the security key
        Given the User "user@example.org" has the security key "YubiKey"
        When I am on "/login"
        And I fill in "_username" with "user@example.org"
        And I fill in "_password" with "asdasd"
        And I press "Sign in"
        Then I should be on "/2fa"
        And I should see "Use security key"
        And I should see "Use a backup code"

    Scenario: Login with a backup code for a user with only a security key
        Given the User "user@example.org" has the security key "YubiKey"
        And the User "user@example.org" has TOTP backup codes "111111,222222"
        When I am on "/login"
        And I fill in "_username" with "user@example.org"
        And I fill in "_password" with "asdasd"
        And I press "Sign in"
        Then I should be on "/2fa"
        When I submit the two-factor code "111111"
        Then I should be on "/account"
        When I am on "/account/twofactor"
        Then I should see "One code left"
