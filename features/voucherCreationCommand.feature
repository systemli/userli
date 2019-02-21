Feature: VoucherCreationCommand

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email            | password |
      | user@example.org | password |

  @voucherCreationCommand
  Scenario: Create new voucher
    When I run console command "app:voucher:create -u user@example.org -c 1 -p"
    Then I should see regex "|^[a-z_\-0-9]{6}$|i" in the console output

    When I run console command "app:voucher:create -u user@example.org -c 1 -l"
    Then I should see regex "|^https://users.example.org/register/[a-z_\-0-9]{6}$|i" in the console output
