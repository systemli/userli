Feature: Settings (Reserved Names)

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password | roles      |
      | louis@example.org | asdasd   | ROLE_ADMIN |
      | user@example.org  | asdasd   | ROLE_USER  |

  @reserved-names
  Scenario: Normal user cannot access reserved names page
    Given I am authenticated as "user@example.org"
    When I am on "/settings/reserved-names/"

    Then I should see "Access Denied"
    And the response status code should be 403

  @reserved-names
  Scenario: Admin can list reserved names
    Given the following ReservedName exists:
      | name      |
      | admin     |
      | postmaster|
    And I am authenticated as "louis@example.org"
    When I am on "/settings/reserved-names/"

    Then the response status code should be 200
    And I should see "admin"
    And I should see "postmaster"

  @reserved-names
  Scenario: Admin can create a reserved name
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/reserved-names/create"
    Then the response status code should be 200

    When I fill in "reserved_name_name" with "testname"
    And I press "Create"

    Then I should see "Reserved name has been created successfully"
    And I should see "testname"

  @reserved-names
  Scenario: Admin cannot create a duplicate reserved name
    Given the following ReservedName exists:
      | name  |
      | admin |
    And I am authenticated as "louis@example.org"
    When I am on "/settings/reserved-names/create"

    When I fill in "reserved_name_name" with "admin"
    And I press "Create"

    Then I should see "This value is already used"

  @reserved-names
  Scenario: Admin can delete a reserved name
    Given the following ReservedName exists:
      | name      |
      | deleteme  |
    And I am authenticated as "louis@example.org"
    When I am on "/settings/reserved-names/"

    Then I should see "deleteme"

    When I press "Delete"

    Then I should see "Reserved name has been deleted successfully"
    And I should not see "deleteme"

  @reserved-names
  Scenario: Admin can search reserved names
    Given the following ReservedName exists:
      | name      |
      | admin     |
      | postmaster|
      | webmaster |
    And I am authenticated as "louis@example.org"
    When I am on "/settings/reserved-names/?search=admin"

    Then the response status code should be 200
    And I should see "admin"
    And I should not see "postmaster"
    And I should not see "webmaster"

  @reserved-names
  Scenario: Admin can export reserved names
    Given the following ReservedName exists:
      | name      |
      | admin     |
      | postmaster|
    And I am authenticated as "louis@example.org"
    When I am on "/settings/reserved-names/export"

    Then the response status code should be 200
