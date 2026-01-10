Feature: Login

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email               | password | roles           |
      | louis@example.org   | asdasd   | ROLE_ADMIN      |
      | support@example.org | asdasd   | ROLE_MULTIPLIER |
      | user@example.org    | asdasd   | ROLE_USER       |
      | spam@example.org    | asdasd   | ROLE_SPAM       |

  @login
  Scenario: Login as User
    When I am on "/login"
    And I fill in the following:
      | _username | louis@example.org |
      | _password | asdasd            |
    And I press "Sign in"

    Then I should be on "/start"
    And I should see text matching "Log out"
    And the response status code should not be 403

  @login
  Scenario: Login as User
    When I am on "/"
    And I fill in the following:
      | _username | louis@example.org |
      | _password | asdasd            |
    And I press "Sign in"

    Then I should be on "/start"
    And I should see text matching "Log out"
    And the response status code should not be 403

  @login
  Scenario: Login failures
    When I am on "/login"
    And I fill in the following:
      | _username | louis@example.org |
      | _password | test123           |
    And I press "Sign in"

    Then I should see text matching "The presented password is invalid."

  @login
  Scenario: Login as Admin
    When I am on "/login"
    And I fill in the following:
      | _username | user@example.org |
      | _password | asdasd           |
    And I press "Sign in"

    Then I should be on "/start"
    And the response status code should not be 403

  @login
  Scenario: Login as Support
    When I am on "/login"
    And I fill in the following:
      | _username | support@example.org |
      | _password | asdasd              |
    And I press "Sign in"

    Then I should be on "/start"
    And the response status code should not be 403

  @login
  Scenario: Login without domain
    When I am on "/login"
    And I fill in the following:
      | _username | user   |
      | _password | asdasd |
    And I press "Sign in"

    Then I should be on "/start"
    And the response status code should not be 403

  @login
  Scenario: Login with special characters in password
    When the following User exists:
      | email               | password | roles     |
      | special@example.org | paßwort  | ROLE_USER |
    And I am on "/login"
    And I fill in the following:
      | _username | special@example.org |
      | _password | paßwort             |
    And I press "Sign in"

    Then I should be on "/start"
    And the response status code should not be 403

  @logout
  Scenario: Logout
    When I am authenticated as "louis@example.org"
    And I am on "/logout"

    When I am on "/admin/dashboard"
    Then I should be on "/login"

  @logout
  Scenario: Logout
    When I am authenticated as "louis@example.org"
    And I am on "/logout"

    Then I should see text matching "You are now logged out."

  @login
  Scenario: Login as Spam
    When I am on "/login"
    And I fill in the following:
      | _username | spam@example.org |
      | _password | asdasd           |
    And I press "Sign in"

    Then I should be on "/start"
    And the response status code should be 200
    And I should see text matching "E-mail access has been turned off"

  @login-2fa
  Scenario: Login fails with invalid TOTP code if two-factor auth is enabled
    When the following User exists:
      | email                 | password | roles     | totpConfirmed | totpSecret |
      | twofactor@example.org | asdasd   | ROLE_USER | 1             | secret     |
    And I am on "/login"
    And I fill in the following:
      | _username | twofactor@example.org |
      | _password | asdasd                |
    And I press "Sign in"

    Then I should be on "/2fa"
    And I should see text matching "Authentication code"

    And I fill in "_auth_code" with "invalid-token"
    And I press "Verify"

    Then I should be on "/2fa"
    And I should see text matching "The verification code is not valid."

    And I follow "Cancel login"
    Then I should be on "/"
    And the response status code should be 200

  @login-2fa
  Scenario: Login works with two-factor backup code if two-factor auth is enabled
    When the following User exists:
      | email                 | password | roles     | totpConfirmed | totpSecret | totp_backup_codes |
      | twofactor@example.org | asdasd   | ROLE_USER | 1             | secret     | true              |
    And I am on "/login"
    And I fill in the following:
      | _username | twofactor@example.org |
      | _password | asdasd                |
    And I press "Sign in"

    Then I should be on "/2fa"
    And I should see text matching "Authentication code"

    And I enter TOTP backup code
    And I press "Verify"

    Then I should be on "/start"
    And the response status code should be 200
