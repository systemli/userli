Feature: Admin

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
      | example.com |
    And the following User exists:
      | email               | password | roles             |
      | louis@example.org   | asdasd   | ROLE_ADMIN        |
      | domain@example.com  | asdasd   | ROLE_DOMAIN_ADMIN |
      | support@example.org | asdasd   | ROLE_MULTIPLIER   |
      | user@example.org    | asdasd   | ROLE_USER         |

  @admin
  Scenario: Unauthenticated user is redirected to login
    When I am on "/admin/"
    Then I should be on "/login"
    And the response status code should be 200

  @admin
  Scenario: Admin can access admin dashboard
    When I am authenticated as "louis@example.org"
    And I am on "/admin/"
    Then the response status code should be 200
    And I should see "Dashboard"
    And I should see "Users"
    And I should see "Aliases"
    And I should see "Domains"
    And I should see "OpenPGP Keys"
    And I should see "Vouchers redeemed"
    And I should see "Vouchers unredeemed"

  @admin
  Scenario: Domain admin can access admin dashboard
    When I am authenticated as "domain@example.com"
    And I am on "/admin/"
    Then the response status code should be 200
    And I should see "Dashboard"
    And I should see "Users"
    And I should see "Aliases"
    And I should see "OpenPGP Keys"
    And I should not see "Domains"
    And I should not see "Vouchers redeemed"
    And I should not see "Vouchers unredeemed"

  @admin
  Scenario: Domain admin cannot access admin-only pages
    When I am authenticated as "domain@example.com"
    And I am on "/admin/settings"
    Then the response status code should be 403

  @admin
  Scenario: Multiplier cannot access admin page
    When I am authenticated as "support@example.org"
    And I am on "/admin/"
    Then the response status code should be 403

  @admin
  Scenario: Regular user cannot access admin page
    When I am authenticated as "user@example.org"
    And I am on "/admin/"
    Then the response status code should be 403
