Feature: User

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email               | password | roles        |
      | admin@example.org   | asdasd   | ROLE_ADMIN   |
      | user@example.org    | asdasd   | ROLE_USER    |
      | support@example.org | asdasd   | ROLE_MULTIPLIER |
    And the following Alias exists:
      | user_id | source                     | destination       | deleted | random |
      | 2       | alias1@example.org         | user@example.org  | 0       | 0      |
      | 2       | alias2@example.org         | user@example.org  | 1       | 0      |
      | 1       | alias3@example.org         | admin@example.org | 0       | 0      |
      | 2       | random_alias_4@example.org | user@example.org  | 0       | 1      |

  @password-change
  Scenario: Change password
    When I am authenticated as "user@example.org"
    And I am on "/account"
    And I fill in the following:
      | password_change_password           | asdasd       |
      | password_change_newPassword_first  | P4ssW0rd!!!1 |
      | password_change_newPassword_second | P4ssW0rd!!!1 |
    And I press "Submit"

    Then I should be on "/account"
    And I should see text matching "Your new password is now active!"
    And the response status code should not be 403

  @create-random-alias
  Scenario: Create random alias
    When I am authenticated as "user@example.org"
    And I am on "/alias"
    And I press "Generate random alias"

    Then I should be on "/alias"
    And I should see text matching "Your new alias address was created!"
    And the response status code should be 200

  @create-custom-alias
  Scenario: Create custom alias
    When I am authenticated as "user@example.org"
    And I am on "/alias"
    And I fill in the following:
      | create_custom_alias_alias | test_alias |
    And I press "Add"

    Then I should be on "/alias"
    And I should see text matching "Your new alias address was created!"
    And the response status code should be 200

  @delete-alias
  Scenario: Delete custom alias
    When I am authenticated as "user@example.org"
    And I am on "/alias/delete/1"

    Then I should be on "/alias"
    And the response status code should not be 403

  @delete-alias
  Scenario: Delete random alias
    When I am authenticated as "user@example.org"
    And I am on "/alias/delete/4"
    And I fill in the following:
      | delete_alias_password | asdasd |
    And I press "Delete alias"

    Then I should be on "/alias"
    And I should see text matching "Your alias address was successfully deleted!"
    And the response status code should not be 403

  @delete-alias
  Scenario: Deleted alias redirect
    When I am authenticated as "user@example.org"
    And I am on "/alias/delete/2"
    Then I should be on "/alias"
    And the response status code should not be 403

  @delete-alias
  Scenario: Foreign alias redirect
    When I am authenticated as "user@example.org"
    And I am on "/alias/delete/3"
    Then I should be on "/alias"
    And the response status code should not be 403

  @delete-alias
  Scenario: Nonexistent alias redirect
    When I am authenticated as "user@example.org"
    And I am on "/alias/delete/200"
    Then I should be on "/alias"
    And the response status code should not be 403

  @delete-user
  Scenario: Delete Account
    When I am authenticated as "user@example.org"
    And I am on "/user/delete"
    And I fill in the following:
      | delete_user_password | asdasd |
    And I press "Delete account"

    Then I should be on "/"
    And the response status code should not be 403

    And I fill in the following:
      | username | user@example.org |
      | password | asdasd           |
    And I press "Sign in"

    Then I should be on "/login"
    Then I should see text matching "Wrong login details"
    And the response status code should not be 403

  @create-voucher
  Scenario: Create voucher as Admin
    When I am authenticated as "admin@example.org"
    And I am on "/voucher"
    And I press "Create invite code"

    Then I should be on "/voucher"
    And I should see text matching "You created a new invite code!"
    And the response status code should be 200

  @create-voucher
  Scenario: Create voucher as Support
    When I am authenticated as "support@example.org"
    And I am on "/voucher"
    And I press "Create invite code"

    Then I should be on "/voucher"
    And I should see text matching "You created a new invite code!"
    And the response status code should be 200

  Scenario: Voucher button as Support
    When I am authenticated as "support@example.org"
    And I am on "/voucher"

    Then I should see text matching "Create invite code"

  Scenario: Voucher button as User
    When I am authenticated as "user@example.org"
    And I am on "/voucher"

    Then I should not see text matching "Create invite code"

  @generate-recovery-token
  Scenario: Create a new recovery token
    When I am authenticated as "user@example.org"
    And I am on "/user/recovery_token"
    And I fill in the following:
      | recovery_token_password | asdasd |
    And I press "Create new recovery token"

    Then I should see text matching "The following recovery token got created for you"
