Feature: Settings (Vouchers)

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        | invitation_enabled | invitation_limit |
      | example.org | 1                  | 3                |
      | example.com | 1                  | 3                |
    And the following User exists:
      | email              | password | roles             |
      | louis@example.org  | asdasd   | ROLE_ADMIN        |
      | user@example.org   | asdasd   | ROLE_USER         |
      | domain@example.com | asdasd   | ROLE_DOMAIN_ADMIN |

  @vouchers
  Scenario: Normal user cannot access vouchers page
    Given I am authenticated as "user@example.org"
    When I am on "/admin/vouchers/"

    Then I should see "Access Denied"
    And the response status code should be 403

  @vouchers
  Scenario: Admin can list vouchers
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/vouchers/"

    Then the response status code should be 200
    And I should see "Vouchers"

  @vouchers
  Scenario: Admin can access create voucher page
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/vouchers/create"

    Then the response status code should be 200
    And I should see "Create Voucher"

  @vouchers
  Scenario: Admin can filter vouchers by status
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/vouchers/?status=unredeemed"

    Then the response status code should be 200
    And I should see "Vouchers"

  @vouchers
  Scenario: Admin can create a voucher
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/vouchers/create"
    Then the response status code should be 200

    When I fill in "voucher_code" with "abc123"
    And I set hidden field "voucher_user" to "1"
    And I set hidden field "voucher_domain" to "1"
    And I press "Create"

    Then I should see "Voucher has been created successfully"
    And I should see "abc123"

  @vouchers
  Scenario: Admin cannot create a voucher with short code
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/vouchers/create"

    When I fill in "voucher_code" with "ab"
    And I set hidden field "voucher_user" to "1"
    And I set hidden field "voucher_domain" to "1"
    And I press "Create"

    Then the response status code should be 422
    And I should see "Create Voucher"

  @vouchers
  Scenario: Admin can list existing vouchers
    Given the following Voucher exists:
      | code   | user              |
      | vch001 | louis@example.org |
      | vch002 | louis@example.org |
    And I am authenticated as "louis@example.org"
    When I am on "/admin/vouchers/"

    Then the response status code should be 200
    And I should see "vch001"
    And I should see "vch002"

  @javascript @vouchers @delete-modal
  Scenario: Delete voucher via confirmation modal
    Given the following Voucher exists:
      | code   | user              |
      | del123 | louis@example.org |
    And I am authenticated as "louis@example.org"
    When I am on "/admin/vouchers/"
    Then I should see "del123"

    When I press "Delete"
    And I wait for the modal to appear
    Then I should see "Confirm deletion" in the modal

    When I click "Cancel" in the modal
    And I wait for the modal to close
    Then I should see "del123"

    When I press "Delete"
    And I wait for the modal to appear
    When I click "Delete" in the modal

    Then I wait for text "Voucher has been deleted successfully" to appear

  # --- Domain admin scenarios ---

  @vouchers
  Scenario: Domain admin can list vouchers
    Given I am authenticated as "domain@example.com"
    When I am on "/admin/vouchers/"

    Then the response status code should be 200
    And I should see "Vouchers"

  @vouchers
  Scenario: Domain admin can access create voucher page
    Given I am authenticated as "domain@example.com"
    When I am on "/admin/vouchers/create"

    Then the response status code should be 200
    And I should see "Create Voucher"

  @vouchers
  Scenario: Domain admin can create voucher in own domain
    Given I am authenticated as "domain@example.com"
    When I am on "/admin/vouchers/create"
    Then the response status code should be 200

    When I fill in "voucher_code" with "dom123"
    And I set hidden field "voucher_user" to "3"
    And I press "Create"

    Then I should see "Voucher has been created successfully"
    And I should see "dom123"

  @vouchers
  Scenario: Domain admin can only see vouchers from own domain
    Given the following Voucher exists:
      | code   | user               |
      | own001 | domain@example.com |
    And the following Voucher exists:
      | code   | user              |
      | oth001 | louis@example.org |
    And I am authenticated as "domain@example.com"
    When I am on "/admin/vouchers/"

    Then the response status code should be 200
    And I should see "own001"
    And I should not see "oth001"

  @vouchers
  Scenario: Domain admin can delete voucher in own domain
    Given the following Voucher exists:
      | code   | user               |
      | del001 | domain@example.com |
    And I am authenticated as "domain@example.com"
    When I am on "/admin/vouchers/"
    Then I should see "del001"

    When I press the 1st "Delete" button
    Then I should see "Voucher has been deleted successfully"

  @vouchers
  Scenario: Domain admin cannot delete voucher in different domain
    Given the following Voucher exists:
      | code   | user              |
      | xdl001 | louis@example.org |
    And I am authenticated as "domain@example.com"
    And I set the placeholder "__voucher_id__" with property "id" for voucher "xdl001"
    When I request "POST /admin/vouchers/delete/__voucher_id__"

    Then the response status code should be 404
