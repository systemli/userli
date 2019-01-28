Feature: MailCryptCommand

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email                   | password | mailCryptPrivateKey    | mailCryptPublicKey    |
      | mailcrypt@example.org   | password | randomPrivateKeyString | randomPublicKeyString |
      | nomailcrypt@example.org | password |                        |                       |

  @mailCryptCommand
  Scenario: Check if mail_crypt arguments are passed when set
    When I run console command "usrmgmt:users:mailcrypt mailcrypt@example.org"
    Then I should see "randomPrivateKeyString\nrandomPublicKeyString" in the console output

  Scenario: Check if mail_crypt arguments are empty when unset
    When I run console command "usrmgmt:users:mailcrypt nomailcrypt@example.org"
    Then I should see empty console output
