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
  Scenario: Default language
    When I am on "/"
    Then I should see text matching "Welcome"
    Then I am on "/?_locale=de"
    Then I should see text matching "Willkommen"
    Then I am on "/"
    Then I should see text matching "Willkommen"

  @language
  Scenario: Session language
    When I am on "/?_locale=de"
    Then I should see text matching "Willkommen"
    And I am on "/"
    And I should see text matching "Willkommen"

  @language
  Scenario: Browser language detection
    Given set the HTTP-Header "Accept-Language" to "de"
    When I am on "/"
    Then I should see text matching "Willkommen"

  @language
  Scenario: Browser language fallback
    Given set the HTTP-Header "Accept-Language" to "afa"
    When I am on "/"
    Then I should see text matching "Welcome"
