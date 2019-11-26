Feature: Language detection

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |

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
  Scenario: Language detection
    Given set the HTTP-Header "Accept-Language" to "fr"
    And I am on "/"

    Then I should see text matching "Welcome"
