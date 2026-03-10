Feature: password_strength

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password |
      | louis@example.org | asdasd   |
    And the following Voucher exists:
      | code   | user              |
      | TEST00 | louis@example.org |

  @javascript @password_strength
  Scenario: Password strength meter shows minimum length warning for short passwords
    When I am on "/register/TEST00"
    And I fill in "registration[password][first]" with "short"
    And I wait 500 milliseconds
    Then I should see the password strength feedback "At least 12 characters required."
    And I should see 1 active password strength segments

  @javascript @password_strength
  Scenario: Password strength meter shows strong feedback for a strong password
    When I am on "/register/TEST00"
    And I fill in "registration[password][first]" with "C0mpl3x!P@ssw0rd#2024"
    And I wait 500 milliseconds
    Then I should see the password strength feedback "Great choice! Your password is strong."
    And I should see 4 active password strength segments

