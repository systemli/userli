Feature: Initialization

  Background:
    Given the database is clean

  @init
  Scenario: Redirect to init site
    When I am on homepage

    Then I should be on "/init"

  @init
  Scenario: Input admin password
    When the following Domain exists:
      | name        |
      | example.org |
    And I am on "/init/user"
    And I fill in the following:
      | plain_password[plainPassword][first]  | P4ssW0rt!!!1 |
      | plain_password[plainPassword][second] | P4ssW0rt!!!1 |
    And I press "Submit"

    Then I should be on "/"

    @init
    Scenario: No more redirect to init site
      When the following Domain exists:
        | name        |
        | example.org |
      And the following User exists:
        | email                  | password |
        | postmaster@example.org | P4ssW0rt |
      And I am on homepage

      Then I should be on "/"
