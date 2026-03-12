Feature: Admin (Reserved Names)

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
    When I am on "/admin/reserved-names/"

    Then I should see "Access Denied"
    And the response status code should be 403

  @reserved-names
  Scenario: Admin can list reserved names
    Given the following ReservedName exists:
      | name      |
      | admin     |
      | postmaster|
    And I am authenticated as "louis@example.org"
    When I am on "/admin/reserved-names/"

    Then the response status code should be 200
    And I should see "admin"
    And I should see "postmaster"

  @reserved-names
  Scenario: Admin can create a reserved name
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/reserved-names/create"
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
    When I am on "/admin/reserved-names/create"

    When I fill in "reserved_name_name" with "admin"
    And I press "Create"

    Then I should see "This value is already used"

  @reserved-names
  Scenario: Admin can search reserved names
    Given the following ReservedName exists:
      | name      |
      | admin     |
      | postmaster|
      | webmaster |
    And I am authenticated as "louis@example.org"
    When I am on "/admin/reserved-names/?search=admin"

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
    When I am on "/admin/reserved-names/export"

    Then the response status code should be 200

  @reserved-names
  Scenario: Admin can import reserved names from file
    Given File "/tmp/reserved_names.txt" exists with content:
      """
      # This is a comment
      webmaster
      postmaster
      hostmaster
      """
    And I am authenticated as "louis@example.org"
    When I am on "/admin/reserved-names/import"
    Then the response status code should be 200

    When I attach the file "/tmp/reserved_names.txt" to "reserved_name_import_file"
    And I press "Import"

    Then I should see "Import completed. Imported: 3, Skipped: 0."
    And I should see "webmaster"
    And I should see "postmaster"
    And I should see "hostmaster"

  @reserved-names
  Scenario: Admin import skips existing reserved names
    Given the following ReservedName exists:
      | name       |
      | postmaster |
    And File "/tmp/reserved_names_dup.txt" exists with content:
      """
      postmaster
      newname
      """
    And I am authenticated as "louis@example.org"
    When I am on "/admin/reserved-names/import"

    When I attach the file "/tmp/reserved_names_dup.txt" to "reserved_name_import_file"
    And I press "Import"

    Then I should see "Import completed. Imported: 1, Skipped: 1."

  @javascript @reserved-names @delete-modal
  Scenario: Delete reserved name via confirmation modal
    Given the following ReservedName exists:
      | name     |
      | deleteme |
    And I am authenticated as "louis@example.org"
    When I am on "/admin/reserved-names/"
    Then I should see "deleteme"

    When I press "Delete"
    And I wait for the modal to appear
    Then I should see "Confirm deletion" in the modal

    When I click "Cancel" in the modal
    And I wait for the modal to close
    Then I should see "deleteme"

    When I press "Delete"
    And I wait for the modal to appear
    When I click "Delete" in the modal

    Then I should see "Reserved name has been deleted successfully"
