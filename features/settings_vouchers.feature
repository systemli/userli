Feature: Settings (Vouchers)

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password | roles      |
      | louis@example.org | asdasd   | ROLE_ADMIN |
      | user@example.org  | asdasd   | ROLE_USER  |

  @vouchers
  Scenario: Normal user cannot access vouchers page
    Given I am authenticated as "user@example.org"
    When I am on "/settings/vouchers/"

    Then I should see "Access Denied"
    And the response status code should be 403

  @vouchers
  Scenario: Admin can list vouchers
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/vouchers/"

    Then the response status code should be 200
    And I should see "Vouchers"

  @vouchers
  Scenario: Admin can access create voucher page
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/vouchers/create"

    Then the response status code should be 200
    And I should see "Create Voucher"

  @vouchers
  Scenario: Admin can filter vouchers by status
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/vouchers/?status=unredeemed"

    Then the response status code should be 200
    And I should see "Vouchers"
