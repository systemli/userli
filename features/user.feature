Feature: User

  Background:
    Given the database is clean
    And the following Domain exists:
      | name         |
      | example.org |
    And the following User exists:
      | email                | password | roles        |
      | admin@example.org   | asdasd   | ROLE_ADMIN   |
      | user@example.org    | asdasd   | ROLE_USER    |
      | support@example.org | asdasd   | ROLE_SUPPORT |

  @password-change
  Scenario: Change password
    When I am authenticated as "user@example.org"
    And I am on "/"
    And I fill in the following:
      | password_change_password           | asdasd       |
      | password_change_newPassword_first  | P4ssW0rd!!!1 |
      | password_change_newPassword_second | P4ssW0rd!!!1 |
    And I press "Submit"

    Then I should be on "/"
    And I should see text matching "Your new password is now active!"
    And the response status code should not be 403

  Scenario: Delete Account
    When I am authenticated as "user@example.org"
    And I am on "/delete"
    And I fill in the following:
      | delete_password | asdasd |
    And I press "Delete account"

    Then I should be on "/"
    And the response status code should not be 403

    And I fill in the following:
      | username | user@example.org |
      | password | asdasd            |
    And I press "Sign in"

    Then I should be on "/login"
    Then I should see text matching "Wrong login details"
    And the response status code should not be 403

  Scenario: Create voucher as Admin
    When I am authenticated as "admin@example.org"
    And I am on "/"
    And I press "Create voucher"

    Then I should be on "/"
    And I should see text matching "You got a new voucher created!"
    And the response status code should be 200

  Scenario: Create voucher as Support
    When I am authenticated as "support@example.org"
    And I am on "/"
    And I press "Create voucher"

    Then I should be on "/"
    And I should see text matching "You got a new voucher created!"
    And the response status code should be 200

  Scenario: Voucher button as Support
    When I am authenticated as "support@example.org"
    And I am on "/"

    Then I should see text matching "Create voucher"

  Scenario: Voucher button as User
    When I am authenticated as "user@example.org"
    And I am on "/"

    Then I should not see text matching "Create voucher"
