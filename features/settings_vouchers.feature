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

  @vouchers
  Scenario: Admin can create a voucher
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/vouchers/create"
    Then the response status code should be 200

    When I fill in "voucher_code" with "abc123"
    And I set the hidden field "voucher_user" to "1"
    And I set the hidden field "voucher_domain" to "1"
    And I press "Create"

    Then I should see "Voucher has been created successfully"
    And I should see "abc123"

  @vouchers
  Scenario: Admin cannot create a voucher with short code
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/vouchers/create"

    When I fill in "voucher_code" with "ab"
    And I set the hidden field "voucher_user" to "1"
    And I set the hidden field "voucher_domain" to "1"
    And I press "Create"

    Then the response status code should be 422
    And I should see "Create Voucher"

  @vouchers
  Scenario: Admin can delete a voucher
    Given the following Voucher exists:
      | code   | user              |
      | del123 | louis@example.org |
    And I am authenticated as "louis@example.org"
    When I am on "/settings/vouchers/"

    Then I should see "del123"

    When I press "Delete"

    Then I should see "Voucher has been deleted successfully"
    And I should not see "del123"

  @vouchers
  Scenario: Admin can list existing vouchers
    Given the following Voucher exists:
      | code   | user              |
      | vch001 | louis@example.org |
      | vch002 | louis@example.org |
    And I am authenticated as "louis@example.org"
    When I am on "/settings/vouchers/"

    Then the response status code should be 200
    And I should see "vch001"
    And I should see "vch002"
