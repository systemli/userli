Feature: Admin (OpenPGP Keys)

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
    When I am on "/admin/openpgp-keys/"

    Then I should see "Access Denied"
    And the response status code should be 403

  @openpgp-keys
  Scenario: Admin can list OpenPGP keys
    Given the following OpenPgpKey exists:
      | email             | keyId            | keyFingerprint                           | keyData    |
      | louis@example.org | AAAA1111BBBB2222 | AAAA1111BBBB2222CCCC3333DDDD4444EEEE5555 | dummydata1 |
      | user@example.org  | FFFF6666GGGG7777 | FFFF6666GGGG7777HHHH8888IIII9999JJJJ0000 | dummydata2 |
    And I am authenticated as "louis@example.org"
    When I am on "/admin/openpgp-keys/"

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
    When I am on "/admin/openpgp-keys/?search=louis"

    Then the response status code should be 200
    And I should see "louis@example.org"
    And I should not see "user@example.org"

  @openpgp-keys
  Scenario: Domain admin can access OpenPGP keys page scoped to their domain
    Given the following Domain exists:
      | name        |
      | example.com |
    And the following User exists:
      | email              | password | roles             |
      | domain@example.com | asdasd   | ROLE_DOMAIN_ADMIN |
    And the following OpenPgpKey exists:
      | email              | keyId            | keyFingerprint                           | keyData    |
      | user@example.org   | AAAA1111BBBB2222 | AAAA1111BBBB2222CCCC3333DDDD4444EEEE5555 | dummydata1 |
      | domain@example.com | CCCC3333DDDD4444 | CCCC3333DDDD4444EEEE5555FFFF6666AAAA1111 | dummydata2 |
    And I am authenticated as "domain@example.com"
    When I am on "/admin/openpgp-keys/"

    Then the response status code should be 200
    And I should see "domain@example.com"
    And I should not see "user@example.org"
