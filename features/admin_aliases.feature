Feature: Admin (Aliases)

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password | roles      |
      | louis@example.org | asdasd   | ROLE_ADMIN |
      | user@example.org  | asdasd   | ROLE_USER  |

  @aliases
  Scenario: Normal user cannot access aliases page
    Given I am authenticated as "user@example.org"
    When I am on "/admin/aliases/"

    Then I should see "Access Denied"
    And the response status code should be 403

  @aliases
  Scenario: Admin can list aliases
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/aliases/"

    Then the response status code should be 200
    And I should see "Aliases"

  @aliases
  Scenario: Admin can access create alias page
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/aliases/create"

    Then the response status code should be 200
    And I should see "Create new Alias"

  @aliases
  Scenario: Admin can filter aliases by status
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/aliases/?deleted=deleted"

    Then the response status code should be 200
    And I should see "Aliases"

  @aliases
  Scenario: Admin can create an alias
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/aliases/create"
    Then the response status code should be 200

    When I fill in "alias_admin_source" with "newalias@example.org"
    And I press "Create"

    Then I should see "Alias has been created successfully"

  @aliases
  Scenario: Admin can list existing aliases
    Given the following Alias exists:
      | source              | destination       |
      | alias1@example.org  | louis@example.org |
      | alias2@example.org  | user@example.org  |
    And I am authenticated as "louis@example.org"
    When I am on "/admin/aliases/"

    Then the response status code should be 200
    And I should see "alias1@example.org"
    And I should see "alias2@example.org"

  @aliases
  Scenario: Admin can access edit alias page
    Given the following Alias exists:
      | source              | destination       |
      | editalias@example.org | louis@example.org |
    And I am authenticated as "louis@example.org"
    When I am on "/admin/aliases/"

    Then I should see "editalias@example.org"

    When I follow "Edit"

    Then the response status code should be 200
    And I should see "Edit Alias"

  @aliases
  Scenario: Admin can edit an alias destination
    Given the following Alias exists:
      | source                | destination       |
      | editalias@example.org | louis@example.org |
    And I am authenticated as "louis@example.org"
    When I am on "/admin/aliases/"
    And I follow "Edit"
    Then I should see "Edit Alias"

    When I fill in "alias_admin_destination" with "user@example.org"
    And I press "Save"

    Then I should see "Alias has been updated successfully"

  @javascript @aliases @delete-modal
  Scenario: Delete alias via confirmation modal
    Given the following Alias exists:
      | source               | destination       |
      | todelete@example.org | louis@example.org |
    And I am authenticated as "louis@example.org"
    When I am on "/admin/aliases/"
    Then I should see "todelete@example.org"

    When I press "Delete"
    And I wait for the modal to appear
    Then I should see "Confirm deletion" in the modal

    When I click "Cancel" in the modal
    And I wait for the modal to close
    Then I should see "todelete@example.org"

    When I press "Delete"
    And I wait for the modal to appear
    When I click "Delete" in the modal

    Then I should see "Alias has been deleted successfully"
