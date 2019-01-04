Feature: CheckUserCommand

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email               | password | roles        |
      | user@example.org    | password | ROLE_USER    |
      | spam@example.org    | password | ROLE_SPAM    |

  @checkUserCommand
  Scenario: Check if user exists
    When I run console command "-v usrmgmt:users:check user@example.org"
    Then I should see "OK" in the output

    When I run console command "-v usrmgmt:users:check spam@example.org"
    Then I should see "OK" in the output

    When I run console command "-v usrmgmt:users:check non-existent@example.org"
    Then I should see "FAIL" in the output

  @checkUserCommand
  Scenario: Check if password allows for authentication
    When I run console command "-v usrmgmt:users:check user@example.org password"
    Then I should see "OK" in the output

    When I run console command "-v usrmgmt:users:check user@example.org false"
    Then I should see "FAIL" in the output

    When I run console command "-v usrmgmt:users:check spam@example.org password"
    Then I should see "FAIL" in the output