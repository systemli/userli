Feature: Settings (OpenPGP Keys)

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password | roles      |
      | louis@example.org | asdasd   | ROLE_ADMIN |
      | user@example.org  | asdasd   | ROLE_USER  |

  @openpgp-keys
  Scenario: Normal user cannot access OpenPGP keys page
    Given I am authenticated as "user@example.org"
    When I am on "/settings/openpgp-keys/"

    Then I should see "Access Denied"
    And the response status code should be 403

  @openpgp-keys
  Scenario: Admin can list OpenPGP keys
    Given the following OpenPgpKey exists:
      | email             | keyId            | keyFingerprint                           | keyData    |
      | louis@example.org | AAAA1111BBBB2222 | AAAA1111BBBB2222CCCC3333DDDD4444EEEE5555 | dummydata1 |
      | user@example.org  | FFFF6666GGGG7777 | FFFF6666GGGG7777HHHH8888IIII9999JJJJ0000 | dummydata2 |
    And I am authenticated as "louis@example.org"
    When I am on "/settings/openpgp-keys/"

    Then the response status code should be 200
    And I should see "louis@example.org"
    And I should see "user@example.org"
    And I should see "AAAA1111BBBB2222"
    And I should see "FFFF6666GGGG7777"

  @openpgp-keys
  Scenario: Admin can search OpenPGP keys
    Given the following OpenPgpKey exists:
      | email             | keyId            | keyFingerprint                           | keyData    |
      | louis@example.org | AAAA1111BBBB2222 | AAAA1111BBBB2222CCCC3333DDDD4444EEEE5555 | dummydata1 |
      | user@example.org  | FFFF6666GGGG7777 | FFFF6666GGGG7777HHHH8888IIII9999JJJJ0000 | dummydata2 |
    And I am authenticated as "louis@example.org"
    When I am on "/settings/openpgp-keys/?search=louis"

    Then the response status code should be 200
    And I should see "louis@example.org"
    And I should not see "user@example.org"

  @openpgp-keys
  Scenario: Admin can access import page
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/openpgp-keys/import"

    Then the response status code should be 200

  @openpgp-keys
  Scenario: Admin sees error when importing key with invalid text
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/openpgp-keys/import"
    And I fill in the following:
      | upload_openpgp_key_email         | user@example.org |
      | upload_openpgp_key_keyFile       |                  |
      | upload_openpgp_key_keyText       | invalid key data |
    And I press "Publish OpenPGP key"

    Then I should see "doesn't contain valid OpenPGP data"

  @openpgp-keys
  Scenario: Admin sees error when importing with both fields empty
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/openpgp-keys/import"
    And I fill in the following:
      | upload_openpgp_key_email         | user@example.org |
      | upload_openpgp_key_keyFile       |                  |
      | upload_openpgp_key_keyText       |                  |
    And I press "Publish OpenPGP key"

    Then I should see "Please upload key either as file or as ASCII text."
    And the response status code should be 422

  @openpgp-keys
  Scenario: Admin can import OpenPGP key via file upload
    Given File "/tmp/alice1_settings.asc" exists with content:
      """
      -----BEGIN PGP PUBLIC KEY BLOCK-----

      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
      hMNhWBRccEo8lQr8xtvkrfY=
      =K+Hi
      -----END PGP PUBLIC KEY BLOCK-----
      """
    And the following User exists:
      | email             | password |
      | alice@example.org | asdasd   |
    And I am authenticated as "louis@example.org"
    When I am on "/settings/openpgp-keys/import"
    And I fill in the following:
      | upload_openpgp_key_email         | alice@example.org            |
      | upload_openpgp_key_keyFile       | /tmp/alice1_settings.asc     |
      | upload_openpgp_key_keyText       |                              |
    And I press "Publish OpenPGP key"

    Then I should see "OpenPGP key has been imported successfully"

  @openpgp-keys
  Scenario: Admin sees error when importing key for wrong email
    Given File "/tmp/james_settings.asc" exists with content:
      """
      -----BEGIN PGP PUBLIC KEY BLOCK-----

      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
      hx77izIzoqOrwcQ7yTyR+Uo=
      =hivm
      -----END PGP PUBLIC KEY BLOCK-----
      """
    And I am authenticated as "louis@example.org"
    When I am on "/settings/openpgp-keys/import"
    And I fill in the following:
      | upload_openpgp_key_email         | user@example.org           |
      | upload_openpgp_key_keyFile       | /tmp/james_settings.asc    |
      | upload_openpgp_key_keyText       |                            |
    And I press "Publish OpenPGP key"

    Then I should see "No OpenPGP keys found in the provided data"

  @javascript @openpgp-keys @delete-modal
  Scenario: Delete OpenPGP key via confirmation modal
    Given the following OpenPgpKey exists:
      | email             | keyId            | keyFingerprint                           | keyData    |
      | user@example.org  | AAAA1111BBBB2222 | AAAA1111BBBB2222CCCC3333DDDD4444EEEE5555 | dummydata1 |
    And I am authenticated as "louis@example.org"
    When I am on "/settings/openpgp-keys/"
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

    Then I should see "OpenPGP key has been deleted successfully"
