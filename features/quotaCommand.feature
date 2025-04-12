Feature: QuotaCommand

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email               | password | quota |
      | noquota@example.org | password |       |
      | quota@example.org   | password | 1000  |

  @quotaCommand
  Scenario: Check that user has quota
    When I run console command "app:users:quota --user quota@example.org"
    Then I should see "1000" in the console output

    When I run console command "app:users:quota --user noquota@example.org"
    Then I should not see "1000" in the console output
