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
      | support@example.org | asdasd   | ROLE_SUPPORT      |
      | user@example.org    | asdasd   | ROLE_USER         |
    And the following Voucher exists:
      | code | user             |
      | TEST | adminexample.org |
    And the following Alias exists:
      | source            | destination       |
      | admin@example.org | louis@example.org |
    And the following ReservedName exists:
      | name      |
      | forbidden |

  @admin
  Scenario: Access to Admin Interface as Admin
    When I am on "/admin/dashboard"
    Then I should be on "/login"
    And the response status code should be 200

    When I am authenticated as "louis@example.org"
    And I am on "/admin/dashboard"
    Then the response status code should be 200
    And I should see text matching "Logout"
    And I should see text matching "Return to Index"

  @admin
  Scenario: Access to Admin Interface as Domain Admin
    When I am authenticated as "domain@example.com"
    And I am on "/admin/dashboard"
    Then the response status code should be 200
    And I should see text matching "Logout"
    And I should see text matching "Return to Index"

  @admin
  Scenario: Access to Admin Interface as Support
    When I am authenticated as "support@example.org"
    And I am on "/admin/dashboard"
    Then the response status code should be 403

  @admin
  Scenario: Access to Admin Interface as User
    When I am authenticated as "user@example.org"
    And I am on "/admin/dashboard"
    Then the response status code should be 403

  @admin
  Scenario: Access User List and able to create a User as Admin
    When I am authenticated as "louis@example.org"
    And I am on "/admin/user/list"
    Then the response status code should be 200
    And I should see "example.com"

    When I am on "/admin/user/create"
    Then the response status code should be 200

  @admin
  Scenario: Access User List and able to create a User as Domain Admin
    When I am authenticated as "domain@example.com"
    And I am on "/admin/user/list"
    Then the response status code should be 200
    And I should not see "example.org"

    When I am on "/admin/user/create"
    Then the response status code should be 200

  @admin
  Scenario: Access User List and able to create a User as Support
    When I am authenticated as "support@example.org"
    And I am on "/admin/user/list"
    Then the response status code should be 403

    When I am on "/admin/user/create"
    Then the response status code should be 403

  @admin
  Scenario: Access Domain List and able to create a Domain as Admin
    When I am authenticated as "louis@example.org"
    And I am on "/admin/domain/list"
    Then the response status code should be 200

    When I am on "/admin/domain/create"
    Then the response status code should be 200

  @admin
  Scenario: Access Domain List and able to create a Domain as Domain Admin
    When I am authenticated as "domain@example.com"
    And I am on "/admin/domain/list"
    Then the response status code should be 403

    When I am on "/admin/domain/create"
    Then the response status code should be 403

  @admin
  Scenario: Access Domain List and able to create a Domain as Support
    When I am authenticated as "support@example.org"
    And I am on "/admin/domain/list"
    Then the response status code should be 403

    When I am on "/admin/domain/create"
    Then the response status code should be 403

  @admin
  Scenario: Access Alias List and able to create a Alias as Admin
    When I am authenticated as "louis@example.org"
    And I am on "/admin/alias/list"
    Then the response status code should be 200

    When I am on "/admin/alias/create"
    Then the response status code should be 200

  @admin
  Scenario: Access Alias List and able to create a Alias as Domain Admin
    When I am authenticated as "domain@example.com"
    And I am on "/admin/alias/list"
    Then the response status code should be 200

    When I am on "/admin/alias/create"
    Then the response status code should be 200

  @admin
  Scenario: Access Alias List and able to create a Alias as Support
    When I am authenticated as "support@example.org"
    And I am on "/admin/alias/list"
    Then the response status code should be 403

    When I am on "/admin/alias/create"
    Then the response status code should be 403

  @admin
  Scenario: Access Voucher List and able to create a Voucher as Admin
    When I am authenticated as "louis@example.org"
    And I am on "/admin/voucher/list"
    Then the response status code should be 200

    When I am on "/admin/voucher/create"
    Then the response status code should be 200

  @admin
  Scenario: Access Voucher List and able to create a Voucher as Support
    When I am authenticated as "support@example.org"
    And I am on "/admin/voucher/list"
    Then the response status code should be 403

    When I am on "/admin/voucher/create"
    Then the response status code should be 403

  @admin
  Scenario: Access ReservedName List and able to create a ReservedName as Admin
    When I am authenticated as "louis@example.org"
    And I am on "/admin/reservedname/list"
    Then the response status code should be 200

    When I am on "/admin/reservedname/create"
    Then the response status code should be 200

  @admin
  Scenario: Access ReservedName List and able to create a ReservedName as Domain Admin
    When I am authenticated as "domain@example.com"
    And I am on "/admin/reservedname/list"
    Then the response status code should be 403

    When I am on "/admin/reservedname/create"
    Then the response status code should be 403

  @admin
  Scenario: Admin can remove Vouchers, Aliases and ReservedNames
    When I am authenticated as "louis@example.org"
    And I am on "/admin/voucher/1/delete"
    Then the response status code should be 200

    When I am on "/admin/alias/1/delete"
    Then the response status code should be 200

    When I am on "/admin/reservedname/1/delete"
    Then the response status code should be 200

  @admin
  Scenario: Admin can't remove Domains
    When I am authenticated as "louis@example.org"
    And I am on "/admin/domain/1/delete"
    Then the response status code should be 404

  @admin
  Scenario: Admin can remove Users
    When I am authenticated as "louis@example.org"
    And I am on "/admin/user/1/delete"
    Then the response status code should be 200

  @admin
  Scenario: Remove unused vouchers
    When I am authenticated as "louis@example.org"
    And I am on "/admin/user/list"
    And I check "all_elements"
    And I select "remove_vouchers" from "action"
    And I press "OK"
    Then I should be on "/admin/user/batch"
    And I press "Yes, execute"
    Then I should be on "/admin/user/list"
    And I should see text matching "Successfully deleted unredeemed vouchers"
