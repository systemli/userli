Feature: Login

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email               | password | roles        |
      | louis@example.org   | asdasd   | ROLE_ADMIN   |
      | support@example.org | asdasd   | ROLE_MULTIPLIER |
      | user@example.org    | asdasd   | ROLE_USER    |
      | spam@example.org    | asdasd   | ROLE_SPAM    |

  @login
  Scenario: Login as User
    When I am on "/login"
    And I fill in the following:
      | username | louis@example.org |
      | password | asdasd            |
    And I press "Sign in"

    Then I should be on "/en/"
    And I should see text matching "Log out"
    And the response status code should not be 403

  @login
  Scenario: Login as User
    When I am on "/"
    And I fill in the following:
      | username | louis@example.org |
      | password | asdasd            |
    And I press "Sign in"

    Then I should be on "/en/"
    And I should see text matching "Log out"
    And the response status code should not be 403

  @login
  Scenario: Login failures
    When I am on "/login"
    And I fill in the following:
      | username | louis@example.org |
      | password | test123           |
    And I press "Sign in"

    Then I should see text matching "The presented password is invalid."

  @login
  Scenario: Login as Admin
    When I am on "/login"
    And I fill in the following:
      | username | user@example.org |
      | password | asdasd           |
    And I press "Sign in"

    Then I should be on "/en/"
    And the response status code should not be 403

  @login
  Scenario: Login as Support
    When I am on "/login"
    And I fill in the following:
      | username | support@example.org |
      | password | asdasd              |
    And I press "Sign in"

    Then I should be on "/en/"
    And the response status code should not be 403

  @login
  Scenario: Login without domain
    When I am on "/login"
    And I fill in the following:
      | username | user   |
      | password | asdasd |
    And I press "Sign in"

    Then I should be on "/en/"
    And the response status code should not be 403

  @login
  Scenario: Login with special characters in password
    When the following User exists:
      | email               | password | roles     |
      | special@example.org | paßwort  | ROLE_USER |
    And I am on "/login"
    And I fill in the following:
      | username | special@example.org |
      | password | paßwort             |
    And I press "Sign in"

    Then I should be on "/en/"
    And the response status code should not be 403

  @logout
  Scenario: Logout
    When I am authenticated as "louis@example.org"
    And I am on "/logout"

    When I am on "/admin/dashboard"
    Then I should be on "/en/login"

  @logout
  Scenario: Logout
    When I am authenticated as "louis@example.org"
    And I am on "/logout"

    Then I should see text matching "You are now logged out."

  @login
  Scenario: Login as Spam
    When I am on "/login"
    And I fill in the following:
      | username | spam@example.org |
      | password | asdasd              |
    And I press "Sign in"

    Then I should be on "/en/"
    And the response status code should be 200
    And I should see text matching "E-mail access has been turned off"
