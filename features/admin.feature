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
    And the following User exists:
      | email                  | password | roles     | mailCrypt | mailCryptSecretBox | mailCryptPublicKey | totpConfirmed | totpSecret       |
      | cryptuser@example.org  | asdasd   | ROLE_USER | 1         | secretbox123       | publickey456       | 1             | JBSWY3DPEHPK3PXP |
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
  Scenario: Create a new User as Admin
    When I am authenticated as "louis@example.org"
    And I am on "/admin/user/create"
    And I fill in the following:
      | Email            | newuser@example.org |
      | Password         | P4ssW0rd!!!1        |
      | Confirm password | P4ssW0rd!!!1        |
    And I press "btn_create_and_list"
    Then the response status code should be 200
    And the user "newuser@example.org" should exist

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
    And I am on "/settings/reserved-names/"
    Then the response status code should be 200

    When I am on "/settings/reserved-names/create"
    Then the response status code should be 200

  @admin
  Scenario: Access ReservedName List and able to create a ReservedName as Domain Admin
    When I am authenticated as "domain@example.com"
    And I am on "/settings/reserved-names/"
    Then the response status code should be 403

    When I am on "/settings/reserved-names/create"
    Then the response status code should be 403

  @admin
  Scenario: Admin can remove Vouchers and Aliases
    When I am authenticated as "louis@example.org"
    And I am on "/admin/voucher/1/delete"
    Then the response status code should be 200

    When I am on "/admin/alias/1/delete"
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
    And I select "removeVouchers" from "action"
    And I press "OK"
    Then I should be on "/admin/user/batch"
    And I press "Yes, execute"
    Then I should be on "/admin/user/list"
    And I should see text matching "Unredeemed invite codes deleted"

  @admin
  Scenario: Admin can batch delete users
    When I am authenticated as "louis@example.org"
    And I am on "/admin/user/list"
    And I check "all_elements"
    And I select "delete" from "action"
    And I press "OK"
    Then I should be on "/admin/user/batch"
    And I press "Yes, execute"
    Then I should be on "/login"

  @admin
  Scenario: Admin can list user notifications
    When I am authenticated as "louis@example.org"
    And I am on "/admin/user-notification/list"
    Then the response status code should be 200

  @admin
  Scenario: It is not possible to create new user notifications
    When I am authenticated as "louis@example.org"
    And I am on "/admin/user-notification/create"
    Then the response status code should be 404

  @admin
  Scenario: It is not possible to edit user notifications
    When I am authenticated as "louis@example.org"
    And I am on "/admin/user-notification/1/edit"
    Then the response status code should be 404

  @admin
  Scenario: Admin resets password of user without MailCrypt
    When I am authenticated as "louis@example.org"
    And I am on "/admin/user/4/edit"
    Then the response status code should be 200
    And I should not see "Warning:"

    When I fill in the following:
      | Password         | N3wP4ssW0rd!!!1 |
      | Confirm password | N3wP4ssW0rd!!!1 |
    And I press "btn_update_and_list"
    Then the response status code should be 200
    And the user "user@example.org" should have passwordChangeRequired

  @admin
  Scenario: Admin resets password of user with MailCrypt
    When I am authenticated as "louis@example.org"
    And I am on "/admin/user/5/edit"
    Then the response status code should be 200
    And I should see "Warning:"

    When I fill in the following:
      | Password         | N3wP4ssW0rd!!!1 |
      | Confirm password | N3wP4ssW0rd!!!1 |
    And I press "btn_update_and_list"
    Then the response status code should be 200
    And the user "cryptuser@example.org" should have passwordChangeRequired
    And the user "cryptuser@example.org" should have a mailCryptSecretBox
    And the user "cryptuser@example.org" should not have totpConfirmed
    And I should see "Recovery Token:"
