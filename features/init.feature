Feature: Initialization

  Background:
    Given the database is clean

  @init
  Scenario: Redirect to init site
    When I am on homepage

    Then I should be on "/en/init"

  @init
  Scenario: Input admin password
    When I am on "/en/init"
    And I fill in the following:
      | plain_password[newPassword][first]  | P4ssW0rt!!!1 |
      | plain_password[newPassword][second] | P4ssW0rt!!!1 |
    And I press "Submit"

    Then I should be on "/en/"

    @init
    Scenario: No more redirect to init site
      When the following Domain exists:
        | name        |
        | example.org |
      And I am on homepage

      Then I should be on "/en/"
