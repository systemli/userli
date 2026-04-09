Feature: Admin (Maintenance)

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password | roles      |
      | louis@example.org | asdasd   | ROLE_ADMIN |
      | user@example.org  | asdasd   | ROLE_USER  |

  @maintenance
  Scenario: Normal user cannot access maintenance page
    Given I am authenticated as "user@example.org"
    When I am on "/admin/maintenance"
    Then the response status code should be 403

  @maintenance
  Scenario: Admin can access maintenance page
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/maintenance"
    Then the response status code should be 200
    And I should see "Maintenance"
    And I should see "Prune User Notifications"
    And I should see "Prune Webhook Deliveries"
    And I should see "Remove Inactive Users"
    And I should see "Unlink Redeemed Invite Codes"
    And I should see "Remove Unredeemed Invite Codes"

  @maintenance
  Scenario: Admin can dispatch prune user notifications task
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/maintenance"
    And I press the 1st "Run pruning" button
    Then I should see "Maintenance task dispatched successfully"

  @maintenance
  Scenario: Admin can dispatch prune webhook deliveries task
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/maintenance"
    And I press the 2nd "Run pruning" button
    Then I should see "Maintenance task dispatched successfully"

  @maintenance
  Scenario: Admin can dispatch remove inactive users task
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/maintenance"
    And I press "Remove inactive users"
    Then I should see "Maintenance task dispatched successfully"

  @maintenance
  Scenario: Admin can dispatch unlink redeemed vouchers task
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/maintenance"
    And I press "Unlink invite codes"
    Then I should see "Maintenance task dispatched successfully"

  @maintenance
  Scenario: Admin can dispatch remove unredeemed vouchers task
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/maintenance"
    And I press "Remove unredeemed invite codes"
    Then I should see "Maintenance task dispatched successfully"
