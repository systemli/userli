Feature: Login

  Background:
    Given the database is clean
    And the following Domain exists:
      | name         |
      | systemli.org |
    And the following User exists:
      | email                | password | roles        |
      | louis@systemli.org   | asdasd   | ROLE_ADMIN   |
      | support@systemli.org | asdasd   | ROLE_SUPPORT |
      | user@systemli.org    | asdasd   | ROLE_USER    |

  @login
  Scenario: Login as User
    When I am on "/login"
    And I fill in the following:
      | username | louis@systemli.org |
      | password | asdasd             |
    And I press "Sign in"

    Then I should be on "/"
    And I should see text matching "Logout"
    And the response status code should not be 403

  @login
  Scenario: Login as User
    When I am on "/"
    And I fill in the following:
      | username | louis@systemli.org |
      | password | asdasd             |
    And I press "Sign in"

    Then I should be on "/"
    And I should see text matching "Logout"
    And the response status code should not be 403

  @login
  Scenario: Login failures
    When I am on "/login"
    And I fill in the following:
      | username | louis@systemli.org |
      | password | test123            |
    And I press "Sign in"

    Then I should see text matching "Wrong login details"

  @login
  Scenario: Login as Admin
    When I am on "/login"
    When I fill in the following:
      | username | user@systemli.org |
      | password | asdasd            |
    And I press "Sign in"

    Then I should be on "/"
    And the response status code should not be 403

  @login
  Scenario: Login as Support
    When I am on "/login"
    When I fill in the following:
      | username | support@systemli.org |
      | password | asdasd               |
    And I press "Sign in"

    Then I should be on "/"
    And the response status code should not be 403

  @login
  Scenario: Login without domain
    When I am on "/login"
    When I fill in the following:
      | username | user   |
      | password | asdasd |
    And I press "Sign in"

    Then I should be on "/"
    And the response status code should not be 403

  @login
  Scenario: Login with special characters in password
    When the following User exists:
      | email                | password | roles     |
      | special@systemli.org | paßwort  | ROLE_USER |
    And I am on "/login"
    And I fill in the following:
      | username | special@systemli.org |
      | password | paßwort              |
    And I press "Sign in"

    Then I should be on "/"
    And the response status code should not be 403

  @logout
  Scenario: Logout
    When I am authenticated as "louis@systemli.org"
    And I am on "/logout"

    When I am on "/admin/dashboard"
    Then I should be on "/login"

  @logout
  Scenario: Logout
    When I am authenticated as "louis@systemli.org"
    And I am on "/logout"

    Then I should see text matching "You have been successfully logged out!"
