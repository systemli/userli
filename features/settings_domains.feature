Feature: Settings (Domains)

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password | roles      |
      | louis@example.org | asdasd   | ROLE_ADMIN |
      | user@example.org  | asdasd   | ROLE_USER  |

  @domains
  Scenario: Normal user cannot access domains page
    Given I am authenticated as "user@example.org"
    When I am on "/settings/domains/"

    Then I should see "Access Denied"
    And the response status code should be 403

  @domains
  Scenario: Admin can list domains
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/domains/"

    Then the response status code should be 200
    And I should see "example.org"

  @domains
  Scenario: Admin can create a domain
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/domains/create"
    Then the response status code should be 200

    When I fill in "domain_name" with "newdomain.org"
    And I press "Create"

    Then I should see "Domain has been created successfully"
    And I should see "newdomain.org"

  @domains
  Scenario: Admin cannot create a duplicate domain
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/domains/create"

    When I fill in "domain_name" with "example.org"
    And I press "Create"

    Then I should see "This value is already used"

  @domains
  Scenario: Admin can view domain details
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/domains/"
    And I follow "example.org"

    Then the response status code should be 200
    And I should see "example.org"
    And I should see "Users"
    And I should see "Aliases"
    And I should see "Domain Admins"

  @domains
  Scenario: Admin can search domains
    Given the following Domain exists:
      | name        |
      | example.com |
      | test.org    |
    And I am authenticated as "louis@example.org"
    When I am on "/settings/domains/?search=example"

    Then the response status code should be 200
    And I should see "example.org"
    And I should see "example.com"
    And I should not see "test.org"
