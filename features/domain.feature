Feature: Domain

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email              | password | roles             |
      | domain@example.org | asdasd   | ROLE_DOMAIN_ADMIN |


  Scenario: Access to Domain Interface as Domain
    When I am on "/domain/settings"
    Then I should be on "/login"
    And the response status code should be 200

    When I am authenticated as "domain@example.org"
    And I am on "/domain/settings"
    Then the response status code should be 200

  Scenario: Create new account
    When I am authenticated as "domain@example.org"
    And I am on "/domain/settings"
    And I fill in the following:
      | basic_registration_email                | user         |
      | basic_registration_plainPassword_first  | P4ssW0rd!!!1 |
      | basic_registration_plainPassword_second | P4ssW0rd!!!1 |
    And I press "Submit"

    Then I should be on "/domain/settings"
    And I should see text matching "Account created successfully."

  Scenario: Create new alias
    When I am authenticated as "domain@example.org"
    And I am on "/domain/settings"
    And I fill in the following:
      | alias_alias | test_alias |
    And I press "Add alias address"

    Then I should be on "/domain/settings"
    And I should see text matching "Alias address created successfully."
