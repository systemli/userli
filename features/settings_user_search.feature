Feature: Settings (User Search)

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password | roles      |
      | louis@example.org | asdasd   | ROLE_ADMIN |
      | user@example.org  | asdasd   | ROLE_USER  |
      | alice@example.org | asdasd   | ROLE_USER  |
      | spam@example.org  | asdasd   | ROLE_SPAM  |

  @user-search
  Scenario: Non-admin user gets access denied
    Given I am authenticated as "user@example.org"
    When I am on "/settings/users/search?q=example"

    Then the response status code should be 403

  @user-search
  Scenario: Admin can search users by email
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/users/search?q=example"

    Then the response should be JSON
    And the JSON response should contain 4 items

  @user-search
  Scenario: Search filters by query
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/users/search?q=alice"

    Then the response should be JSON
    And the JSON response should contain 1 items
    And the JSON path "0.email" should equal "alice@example.org"

  @user-search
  Scenario: Short query returns empty array
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/users/search?q=a"

    Then the response should be JSON
    And the JSON response should contain 0 items

  @user-search
  Scenario: Empty query returns empty array
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/users/search"

    Then the response should be JSON
    And the JSON response should contain 0 items

  @user-search
  Scenario: Search results include roles
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/users/search?q=spam"

    Then the response should be JSON
    And the JSON response should contain 1 items
    And the JSON path "0.email" should equal "spam@example.org"
    And the JSON path "0.roles.0" should equal "ROLE_SPAM"

  @user-search
  Scenario: Search response includes id field
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/users/search?q=alice"

    Then the response should be JSON
    And the JSON path "0.id" should not be empty
