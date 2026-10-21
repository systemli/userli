@app-password
Feature: App passwords

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password | roles     |
      | user@example.org  | asdasd   | ROLE_USER |
      | other@example.org | asdasd   | ROLE_USER |

  Scenario: Create app password
    When I am authenticated as "user@example.org"
    And I am on "/account/app-passwords"
    Then I should see text matching "Current password"
    And I should see text matching "The app password is generated for you"

    When I fill in the following:
      | app_password_create_name     | Thunderbird |
      | app_password_create_password | asdasd      |
    And I press "Generate app password"

    Then the response status code should be 200
    And I should see text matching "Copy your new app password now"
    And I should see text matching "Thunderbird"
    And I should see a "code[data-app-password]" element

  Scenario: List shows when app passwords were last used
    Given the following AppPassword exists:
      | email            | name        | password       | userPassword | lastLoginTime             |
      | user@example.org | Thunderbird | app-password-1 | asdasd       | monday this week midnight |
      | user@example.org | iPhone      | app-password-2 | asdasd       |                           |
    When I am authenticated as "user@example.org"
    And I am on "/account/app-passwords"

    Then I should see text matching "Used this week"
    And I should see text matching "Never used"

  Scenario: List hides webmail credentials
    Given the following AppPassword exists:
      | email            | name             | password       | userPassword | expiresAt |
      | user@example.org | Thunderbird      | app-password-1 | asdasd       |           |
      | user@example.org | Webmail 1a2b3c4d | app-password-2 | asdasd       | +1 hour   |
    When I am authenticated as "user@example.org"
    And I am on "/account/app-passwords"

    Then I should see text matching "Thunderbird"
    And I should not see text matching "Webmail 1a2b3c4d"

  Scenario: Create app password with a name that already exists
    Given the following AppPassword exists:
      | email            | name        | password     | userPassword |
      | user@example.org | Thunderbird | app-password | asdasd       |
    When I am authenticated as "user@example.org"
    And I am on "/account/app-passwords"
    And I fill in the following:
      | app_password_create_name     | Thunderbird |
      | app_password_create_password | asdasd      |
    And I press "Generate app password"

    Then I should see text matching "This value is already used."
    And I should see 1 "[data-app-password-row]" elements
    And I should not see a "code[data-app-password]" element

  Scenario: Create app password with wrong password
    When I am authenticated as "user@example.org"
    And I am on "/account/app-passwords"
    And I fill in the following:
      | app_password_create_name     | Thunderbird |
      | app_password_create_password | wrong       |
    And I press "Generate app password"

    Then I should see text matching "Wrong password"
    And I should not see a "code[data-app-password]" element

  Scenario: Create app password when MailCrypt key is encrypted with another password
    Given the following User exists:
      | email                 | password | roles     | mailCryptPublicKey | mailCryptSecretBox                                                                                   |
      | stale@example.org     | asdasd   | ROLE_USER | public-key         | gCBP1rDKiqKk5FKPSZIXjFT4QP19GNwBWdAT6WujwCsQPLwocCpWBwa91udyJuM2BklX2ZSbGvIGUFA4J/XI1hPE+acItA== |
    When I am authenticated as "stale@example.org"
    And I am on "/account/app-passwords"
    And I fill in the following:
      | app_password_create_name     | Thunderbird |
      | app_password_create_password | asdasd      |
    And I press "Generate app password"

    Then I should see text matching "Your mailbox encryption key could not be unlocked with your password"
    And I should not see a "code[data-app-password]" element
    And I should not see a "[data-app-password-row]" element

  Scenario: List and delete app password
    Given the following AppPassword exists:
      | email            | name        | password     | userPassword |
      | user@example.org | Thunderbird | app-password | asdasd       |
    When I am authenticated as "user@example.org"
    And I am on "/account/app-passwords"
    Then I should see text matching "Thunderbird"

    When I press "Delete"
    Then I should be on "/account/app-passwords"
    And I should see text matching "The app password was deleted."
    And I should not see a "[data-app-password-row]" element

  Scenario: Delete another user's app password
    Given the following AppPassword exists:
      | email             | name        | password     | userPassword |
      | other@example.org | Thunderbird | app-password | asdasd       |
    When I am authenticated as "user@example.org"
    And I request "POST /account/app-passwords/1/delete"

    Then the response status code should be 404

  Scenario: App password does not work for web login
    Given the following AppPassword exists:
      | email            | name        | password     | userPassword |
      | user@example.org | Thunderbird | app-password | asdasd       |
    When I am on "/login"
    And I fill in the following:
      | _username | user@example.org |
      | _password | app-password     |
    And I press "Sign in"

    Then I should be on "/login"
    And I should see text matching "The presented password is invalid."
