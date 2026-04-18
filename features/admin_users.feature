Feature: Settings (Users)

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email               | password | roles             |
      | admin@example.org   | asdasd   | ROLE_ADMIN        |
      | user@example.org    | asdasd   | ROLE_USER         |
      | support@example.org | asdasd   | ROLE_DOMAIN_ADMIN |

  @settings-users
  Scenario: Regular user cannot access users page
    Given I am authenticated as "user@example.org"
    When I am on "/admin/users/"

    Then I should see "Access Denied"
    And the response status code should be 403

  @settings-users
  Scenario: Domain admin can access users page
    Given I am authenticated as "support@example.org"
    When I am on "/admin/users/"

    Then the response status code should be 200
    And I should see "Users"

  @settings-users
  Scenario: Admin can list users
    Given I am authenticated as "admin@example.org"
    When I am on "/admin/users/"

    Then the response status code should be 200
    And I should see "Users"
    And I should see "user@example.org"
    And I should see "support@example.org"

  @settings-users
  Scenario: Admin can search users
    Given I am authenticated as "admin@example.org"
    When I am on "/admin/users/?search=user"

    Then the response status code should be 200
    And I should see "user@example.org"
    And I should not see "support@example.org"

  @settings-users
  Scenario: Admin can filter by deleted status
    Given the following User exists:
      | email               | password | roles     |
      | deleted@example.org | asdasd   | ROLE_USER |
    And I am authenticated as "admin@example.org"
    When I am on "/admin/users/?deleted=deleted"

    Then the response status code should be 200

  @settings-users
  Scenario: Admin can filter by role
    Given I am authenticated as "admin@example.org"
    When I am on "/admin/users/?role=ROLE_DOMAIN_ADMIN"

    Then the response status code should be 200
    And I should see "support@example.org"
    And I should not see "user@example.org"

  @settings-users
  Scenario: Admin can access create user page
    Given I am authenticated as "admin@example.org"
    When I am on "/admin/users/create"

    Then the response status code should be 200
    And I should see "Create new User"

  @settings-users
  Scenario: Admin can create a user
    Given I am authenticated as "admin@example.org"
    When I am on "/admin/users/create"
    Then the response status code should be 200

    When I fill in "user_admin_email" with "new@example.org"
    And I fill in "user_admin_plainPassword_first" with "securePassword123!"
    And I fill in "user_admin_plainPassword_second" with "securePassword123!"
    And I press "Create"

    Then I should see "User has been created successfully"
    And the user "new@example.org" should exist
    And the user "new@example.org" should have passwordChangeRequired

  @settings-users
  Scenario: Admin can create a user with a complex password
    Given I am authenticated as "admin@example.org"
    When I am on "/admin/users/create"
    Then the response status code should be 200

    When I fill in the following:
      | user_admin_email                | complex@example.org            |
      | user_admin_plainPassword_first  | R4MF#7K?L?D#\Q%)F""(yj&KWHtn%^_ |
      | user_admin_plainPassword_second | R4MF#7K?L?D#\Q%)F""(yj&KWHtn%^_ |
    And I press "Create"

    Then I should see "User has been created successfully"
    And the user "complex@example.org" should exist

  @settings-users
  Scenario: Admin sees validation error on empty password
    Given I am authenticated as "admin@example.org"
    When I am on "/admin/users/create"

    When I fill in "user_admin_email" with "test@example.org"
    And I press "Create"

    Then the response status code should be 422
    And I should not see "User has been created successfully"

  @settings-users
  Scenario: Admin sees error on duplicate email
    Given I am authenticated as "admin@example.org"
    When I am on "/admin/users/create"

    When I fill in "user_admin_email" with "user@example.org"
    And I fill in "user_admin_plainPassword_first" with "securePassword123!"
    And I fill in "user_admin_plainPassword_second" with "securePassword123!"
    And I press "Create"

    Then the response status code should be 422
    And I should not see "User has been created successfully"

  @settings-users
  Scenario: Admin can access edit user page
    Given I am authenticated as "admin@example.org"
    And I set the placeholder "__user_id__" with property "id" for "user@example.org"
    When I am on "/admin/users/edit/__user_id__"

    Then the response status code should be 200
    And I should see "Edit User"

  @settings-users
  Scenario: Admin can edit user roles
    Given I am authenticated as "admin@example.org"
    And I set the placeholder "__user_id__" with property "id" for "user@example.org"
    When I am on "/admin/users/edit/__user_id__"

    Then the response status code should be 200

    When I press "Save"

    Then I should see "User has been updated successfully"

  @settings-users
  Scenario: Admin can change user password
    Given I am authenticated as "admin@example.org"
    And I set the placeholder "__user_id__" with property "id" for "user@example.org"
    When I am on "/admin/users/edit/__user_id__"

    When I fill in "user_admin_plainPassword_first" with "newSecurePassword123!"
    And I fill in "user_admin_plainPassword_second" with "newSecurePassword123!"
    And I press "Save"

    Then I should see "User has been updated successfully"
    And the user "user@example.org" should have passwordChangeRequired

  @settings-users
  Scenario: Admin can disable 2FA for a user
    Given the following User exists:
      | email              | password | roles     | totpConfirmed | totpSecret     |
      | totp@example.org   | asdasd   | ROLE_USER | true          | JBSWY3DPEHPK3PXP |
    And I am authenticated as "admin@example.org"
    And I set the placeholder "__user_id__" with property "id" for "totp@example.org"
    When I am on "/admin/users/edit/__user_id__"

    Then the response status code should be 200

    When I uncheck "user_admin_totpConfirmed"
    And I press "Save"

    Then I should see "User has been updated successfully"
    And the user "totp@example.org" should not have totpConfirmed

  @javascript @settings-users @delete-modal
  Scenario: Admin can delete a user via modal
    Given I am authenticated as "admin@example.org"
    When I am on "/admin/users/"
    Then I should see "user@example.org"

    When I press "Delete"
    And I wait for the modal to appear
    Then I should see "Confirm deletion" in the modal

    When I click "Delete" in the modal

    Then I wait for text "User has been deleted successfully" to appear

  @settings-users
  Scenario: Admin can view user show page
    Given I am authenticated as "admin@example.org"
    And I set the placeholder "__user_id__" with property "id" for "user@example.org"
    When I am on "/admin/users/__user_id__"

    Then the response status code should be 200
    And I should see "user@example.org"
    And I should see "User details and statistics"
    And I should see "Status"
    And I should see "2FA"
    And I should see "MailCrypt"
    And I should see "Related Data"
    And I should see "Aliases"
    And I should see "Invite Codes"
    And I should see "OpenPGP Keys"

  @settings-users
  Scenario: Show page displays password compromised warning
    Given the following UserNotification exists:
      | email            | type                 |
      | user@example.org | password_compromised |
    And I am authenticated as "admin@example.org"
    And I set the placeholder "__user_id__" with property "id" for "user@example.org"
    When I am on "/admin/users/__user_id__"

    Then the response status code should be 200
    And I should see "password has been found in a data breach"

  @settings-users
  Scenario: Show page displays password change required warning
    Given the following User exists:
      | email                   | password | roles     | passwordChangeRequired |
      | pwchange@example.org    | asdasd   | ROLE_USER | true                   |
    And I am authenticated as "admin@example.org"
    And I set the placeholder "__user_id__" with property "id" for "pwchange@example.org"
    When I am on "/admin/users/__user_id__"

    Then the response status code should be 200
    And I should see "password change is required on next login"

  @settings-users
  Scenario: Regular user cannot access user show page
    Given I am authenticated as "user@example.org"
    And I set the placeholder "__user_id__" with property "id" for "user@example.org"
    When I am on "/admin/users/__user_id__"

    Then I should see "Access Denied"
    And the response status code should be 403

  @settings-users
  Scenario: Domain admin can view user in own domain
    Given I am authenticated as "support@example.org"
    And I set the placeholder "__user_id__" with property "id" for "user@example.org"
    When I am on "/admin/users/__user_id__"

    Then the response status code should be 200
    And I should see "user@example.org"

  @settings-users
  Scenario: Domain admin cannot view user in other domain
    Given the following Domain exists:
      | name      |
      | other.org |
    And the following User exists:
      | email          | password | roles     |
      | foo@other.org  | asdasd   | ROLE_USER |
    And I am authenticated as "support@example.org"
    And I set the placeholder "__user_id__" with property "id" for "foo@other.org"
    When I am on "/admin/users/__user_id__"

    Then the response status code should be 404

  @settings-users
  Scenario: Domain admin keeps role when editing themselves
    Given I am authenticated as "support@example.org"
    And I set the placeholder "__user_id__" with property "id" for "support@example.org"
    When I am on "/admin/users/edit/__user_id__"
    Then the response status code should be 200

    When I press "Save"
    Then I should see "User has been updated successfully"

    When I am on "/admin/users/"
    Then the response status code should be 200

  @javascript @settings-users @delete-modal
  Scenario: Admin can delete a user via modal on show page
    Given I am authenticated as "admin@example.org"
    And I set the placeholder "__user_id__" with property "id" for "user@example.org"
    When I am on "/admin/users/__user_id__"
    Then I should see "user@example.org"

    When I press "Delete"
    And I wait for the modal to appear
    Then I should see "Confirm deletion" in the modal

    When I click "Cancel" in the modal
    And I wait for the modal to close
    Then I should see "user@example.org"

    When I press "Delete"
    And I wait for the modal to appear
    When I click "Delete" in the modal

    Then I wait for text "User has been deleted successfully" to appear

  @settings-users
  Scenario: Admin can access restore form for deleted user
    Given the following User exists:
      | email               | password | roles     | deleted |
      | deleted@example.org | asdasd   | ROLE_USER | true    |
    And I am authenticated as "admin@example.org"
    And I set the placeholder "__user_id__" with property "id" for "deleted@example.org"
    When I am on "/admin/users/restore/__user_id__"

    Then the response status code should be 200
    And I should see "deleted@example.org"
    And I should see "Restore User"

  @settings-users
  Scenario: Restore form returns 404 for active user
    Given I am authenticated as "admin@example.org"
    And I set the placeholder "__user_id__" with property "id" for "user@example.org"
    When I am on "/admin/users/restore/__user_id__"

    Then the response status code should be 404

  @settings-users
  Scenario: Admin can restore a deleted user
    Given the following User exists:
      | email               | password | roles     | deleted |
      | deleted@example.org | asdasd   | ROLE_USER | true    |
    And I am authenticated as "admin@example.org"
    And I set the placeholder "__user_id__" with property "id" for "deleted@example.org"
    When I am on "/admin/users/restore/__user_id__"
    And I fill in "user_restore_plainPassword_first" with "newSecurePassword123!"
    And I fill in "user_restore_plainPassword_second" with "newSecurePassword123!"
    And I press "Restore"

    Then I should see "User has been restored successfully"

  @settings-users
  Scenario: Restore fails with mismatched passwords
    Given the following User exists:
      | email               | password | roles     | deleted |
      | deleted@example.org | asdasd   | ROLE_USER | true    |
    And I am authenticated as "admin@example.org"
    And I set the placeholder "__user_id__" with property "id" for "deleted@example.org"
    When I am on "/admin/users/restore/__user_id__"
    And I fill in "user_restore_plainPassword_first" with "newSecurePassword123!"
    And I fill in "user_restore_plainPassword_second" with "differentPassword456!"
    And I press "Restore"

    Then the response status code should be 422

  @settings-users
  Scenario: Admin sees restore button on show page of deleted user
    Given the following User exists:
      | email               | password | roles     | deleted |
      | deleted@example.org | asdasd   | ROLE_USER | true    |
    And I am authenticated as "admin@example.org"
    And I set the placeholder "__user_id__" with property "id" for "deleted@example.org"
    When I am on "/admin/users/__user_id__"

    Then the response status code should be 200
    And I should see "Restore"

  @settings-users
  Scenario: Admin does not see restore button for active users on show page
    Given I am authenticated as "admin@example.org"
    And I set the placeholder "__user_id__" with property "id" for "user@example.org"
    When I am on "/admin/users/__user_id__"

    Then the response status code should be 200
    And I should not see "Restore"

  @settings-users
  Scenario: Domain admin cannot restore user from another domain
    Given the following Domain exists:
      | name      |
      | other.org |
    And the following User exists:
      | email              | password | roles     | deleted |
      | deleted@other.org  | asdasd   | ROLE_USER | true    |
    And I am authenticated as "support@example.org"
    And I set the placeholder "__user_id__" with property "id" for "deleted@other.org"
    When I am on "/admin/users/restore/__user_id__"

    Then the response status code should be 404
