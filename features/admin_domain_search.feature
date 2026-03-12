Feature: Settings (Domain Search)

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
      | example.com |
    And the following User exists:
      | email             | password | roles      |
      | louis@example.org | asdasd   | ROLE_ADMIN |
      | user@example.org  | asdasd   | ROLE_USER  |

  @domain-search
  Scenario: Non-admin user gets access denied
    Given I am authenticated as "user@example.org"
    When I am on "/admin/domains/search?q=example"

    Then the response status code should be 403

  @domain-search
  Scenario: Admin can search domains by name
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/domains/search?q=example"

    Then the response should be JSON
    And the JSON response should contain 2 items

  @domain-search
  Scenario: Search filters by name
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/domains/search?q=.com"

    Then the response should be JSON
    And the JSON response should contain 1 items
    And the JSON path "0.name" should equal "example.com"

  @domain-search
  Scenario: Empty query returns all domains
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/domains/search"

    Then the response should be JSON
    And the JSON response should contain 2 items

  @domain-search
  Scenario: Empty query string returns all domains
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/domains/search?q="

    Then the response should be JSON
    And the JSON response should contain 2 items

  @domain-search
  Scenario: No match returns empty array
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/domains/search?q=nonexistent"

    Then the response should be JSON
    And the JSON response should contain 0 items

  @domain-search
  Scenario: Search response includes id field
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/domains/search?q=example.org"

    Then the response should be JSON
    And the JSON path "0.id" should not be empty
    And the JSON path "0.name" should equal "example.org"
