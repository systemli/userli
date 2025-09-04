Feature: Settings

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password | roles      |
      | louis@example.org | asdasd   | ROLE_ADMIN |
      | user@example.org  | asdasd   | ROLE_USER  |

  Scenario: Normal user cannot access settings page
    Given I am authenticated as "user@example.org"
    When I am on "/settings"

    Then I should see "Access Denied"
    And the response status code should be 403

  Scenario: Normal user cannot access API settings page
    Given I am authenticated as "user@example.org"
    When I am on "/settings/api"

    Then I should see "Access Denied"
    And the response status code should be 403

  Scenario: Admin user can access main settings page
    Given I am authenticated as "louis@example.org"
    When I am on "/settings"

    Then I should see "Settings"
    And I should see "Settings Overview"
    And the response status code should be 200

  Scenario: Admin user can access API settings page
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/api"

    Then I should see "API Tokens"
    And the response status code should be 200

  Scenario: Admin user can create a new API token
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/api/create"
    Then I should see "New API Token"

    When I fill in "My new Token" for "api_token_name"
    And I check "keycloak"
    And I check "dovecot"
    And I check "postfix"
    And I press "Create"

    Then I should see "New API token created"
