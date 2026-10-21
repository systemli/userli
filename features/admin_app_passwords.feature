Feature: Admin (App Passwords)

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password | roles      |
      | louis@example.org | asdasd   | ROLE_ADMIN |
      | user@example.org  | asdasd   | ROLE_USER  |

  @app-passwords
  Scenario: Normal user cannot access app passwords page
    Given I am authenticated as "user@example.org"
    When I am on "/admin/app-passwords/"

    Then I should see "Access Denied"
    And the response status code should be 403

  @app-passwords
  Scenario: Admin can list app passwords
    Given the following AppPassword exists:
      | email             | name        | password       | userPassword |
      | louis@example.org | Thunderbird | app-password-1 | asdasd       |
      | user@example.org  | iPhone      | app-password-2 | asdasd       |
    And I am authenticated as "louis@example.org"
    When I am on "/admin/app-passwords/"

    Then the response status code should be 200
    And I should see "Thunderbird"
    And I should see "iPhone"
    And I should not see "app-password-1"

  @app-passwords
  Scenario: Admin sees when app passwords were last used
    Given the following AppPassword exists:
      | email             | name        | password       | userPassword | lastLoginTime |
      | louis@example.org | Thunderbird | app-password-1 | asdasd       | 2026-10-19    |
      | user@example.org  | iPhone      | app-password-2 | asdasd       |               |
    And I am authenticated as "louis@example.org"
    When I am on "/admin/app-passwords/"

    Then the response status code should be 200
    And I should see "Last Used"
    And I should see "10/19/26"
    And I should see "Never"

  @app-passwords
  Scenario: Admin can search app passwords
    Given the following AppPassword exists:
      | email             | name        | password       | userPassword |
      | louis@example.org | Thunderbird | app-password-1 | asdasd       |
      | user@example.org  | iPhone      | app-password-2 | asdasd       |
    And I am authenticated as "louis@example.org"
    When I am on "/admin/app-passwords/?search=user@"

    Then the response status code should be 200
    And I should see "iPhone"
    And I should not see "Thunderbird"

  @app-passwords
  Scenario: Admin user page links to the user's app passwords
    Given the following AppPassword exists:
      | email            | name   | password       | userPassword |
      | user@example.org | iPhone | app-password-2 | asdasd       |
    And I am authenticated as "louis@example.org"
    And I set the placeholder "__user_id__" with property "id" for "user@example.org"
    When I am on "/admin/users/__user_id__"
    And I follow "App Passwords"

    Then the response status code should be 200
    And I should see "iPhone"

  @app-passwords
  Scenario: Domain admin can access app passwords page scoped to their domain
    Given the following Domain exists:
      | name        |
      | example.com |
    And the following User exists:
      | email              | password | roles             |
      | domain@example.com | asdasd   | ROLE_DOMAIN_ADMIN |
    And the following AppPassword exists:
      | email              | name        | password       | userPassword |
      | user@example.org   | iPhone      | app-password-1 | asdasd       |
      | domain@example.com | Thunderbird | app-password-2 | asdasd       |
    And I am authenticated as "domain@example.com"
    When I am on "/admin/app-passwords/"

    Then the response status code should be 200
    And I should see "Thunderbird"
    And I should not see "iPhone"
