Feature: Language detection

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email                  | password | roles        |
      | postmaster@example.org | asdasd   | ROLE_ADMIN   |

  @language
  Scenario: Language detection
    And I am on "/"

    Then I should see text matching "Welcome"

  @language
  Scenario: Language detection
    Given set the HTTP-Header "Accept-Language" to "de"
    And I am on "/"

    Then I should see text matching "Willkommen"

  @language
  Scenario: Missing language fallback
    Given set the HTTP-Header "Accept-Language" to "afa"
    And I am on "/"

    Then I should see text matching "Welcome"
