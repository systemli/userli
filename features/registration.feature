Feature: registration

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password | roles      |
      | louis@example.org | asdasd   | ROLE_ADMIN |
      | suspicious@example.org | asdasd   | ROLE_SUSPICIOUS |
    And the following Voucher exists:
      | code | user              |
      | TEST | louis@example.org |
      | ABCD | suspicious@example.org |
    And the following Alias exists:
      | source            | destination       |
      | alias@example.org | goto@example.org |
    And the following ReservedName exists:
      | name      |
      | webmaster |

  @registration
  Scenario: Register as new user
    When I am on "/register"
    And I fill in the following:
      | registration[voucher]               | TEST         |
      | registration[email]                 | user1        |
      | registration[plainPassword][first]  | P4ssW0rt!!!1 |
      | registration[plainPassword][second] | P4ssW0rt!!!1 |
    And I press "Submit"

    Then I should be on "/register"
    And I should see text matching "Please copy and securely store this recovery token"

    When I am on "/logout"
    Then I should be on "/"

    When I am on "/login"
    And I fill in the following:
      | username | user1@example.org |
      | password | P4ssW0rt!!!1      |
    And I press "Sign in"

    Then I should be on "/start"
    And I should see text matching "Log out"

  @registration
  Scenario: Register with voucher from suspicious user
    When I am on "/register"
    And I fill in the following:
      | registration[voucher]               | ABCD         |
      | registration[email]                 | user1        |
      | registration[plainPassword][first]  | P4ssW0rt!!!1 |
      | registration[plainPassword][second] | P4ssW0rt!!!1 |
    And I press "Submit"

    Then I should be on "/register"
    And I should see "The invite code is invalid."

  @registration
  Scenario: Register with invalid voucher
    When I am on "/register"
    And I fill in the following:
      | registration[voucher]               | INVALID      |
      | registration[email]                 | user1        |
      | registration[plainPassword][first]  | P4ssW0rt!!!1 |
      | registration[plainPassword][second] | P4ssW0rt!!!1 |
    And I press "Submit"

    Then I should be on "/register"
    And I should see "The invite code is invalid."

  @registration
  Scenario: Register with invalid e-mail domain
    When I am on "/register"
    And I fill in the following:
      | registration[voucher]               | TEST                 |
      | registration[email]                 | user@nonexistant.org |
      | registration[plainPassword][first]  | P4ssW0rt!!!1         |
      | registration[plainPassword][second] | P4ssW0rt!!!1         |
    And I press "Submit"

    Then I should be on "/register"
    And I should see "The e-mail is invalid."

  @registration
  Scenario: Register with invalid password
    When I am on "/register"
    And I fill in the following:
      | registration[voucher]               | TEST     |
      | registration[email]                 | user1    |
      | registration[plainPassword][first]  | password |
      | registration[plainPassword][second] | password |
    And I press "Submit"

    Then I should be on "/register"
    And I should see "The password comply not with our security policy."

  @registration
  Scenario: Register with different passwords
    When I am on "/register"
    And I fill in the following:
      | registration[voucher]               | TEST         |
      | registration[email]                 | user1        |
      | registration[plainPassword][first]  | P4ssW0rt!!!1 |
      | registration[plainPassword][second] | P4ssW0rt!!!2 |
    And I press "Submit"

    Then I should be on "/register"
    And I should see "Password and confirmation does not match"

  @registration
  Scenario: Register with taken address
    When I am on "/register"
    And I fill in the following:
      | registration[voucher]               | TEST         |
      | registration[email]                 | louis        |
      | registration[plainPassword][first]  | P4ssW0rt!!!1 |
      | registration[plainPassword][second] | P4ssW0rt!!!1 |
    And I press "Submit"

    Then I should be on "/register"
    And I should see "The e-mail address is already taken."

  @registration
  Scenario: Register with taken alias address
    When I am on "/register"
    And I fill in the following:
      | registration[voucher]               | TEST         |
      | registration[email]                 | alias        |
      | registration[plainPassword][first]  | P4ssW0rt!!!1 |
      | registration[plainPassword][second] | P4ssW0rt!!!1 |
    And I press "Submit"

    Then I should be on "/register"
    And I should see "The e-mail address is already taken."

  @registration
  Scenario: Register with reserved address
    When I am on "/register"
    And I fill in the following:
      | registration[voucher]               | TEST         |
      | registration[email]                 | webmaster    |
      | registration[plainPassword][first]  | P4ssW0rt!!!1 |
      | registration[plainPassword][second] | P4ssW0rt!!!1 |
    And I press "Submit"

    Then I should be on "/register"
    And I should see "The e-mail address is already taken."

  @registration
  Scenario: Register with reserved address
    When I am on "/register"
    And I fill in the following:
      | registration[voucher]               | TEST         |
      | registration[email]                 | Webmaster    |
      | registration[plainPassword][first]  | P4ssW0rt!!!1 |
      | registration[plainPassword][second] | P4ssW0rt!!!1 |
    And I press "Submit"

    Then I should be on "/register"
    And I should see "The e-mail address is already taken."

  @registration
  Scenario: Register with too short username
    When I am on "/register"
    And I fill in the following:
      | registration[voucher]               | TEST         |
      | registration[email]                 | ab           |
      | registration[plainPassword][first]  | P4ssW0rt!!!1 |
      | registration[plainPassword][second] | P4ssW0rt!!!1 |
    And I press "Submit"

    Then I should be on "/register"
    And I should see "The username must contain at least 3 characters."

  @registration
  Scenario: Register with too long username
    When I am on "/register"
    And I fill in the following:
      | registration[voucher]               | TEST                                 |
      | registration[email]                 | abcdefghijklmnopqrstuvwxyz0123456789 |
      | registration[plainPassword][first]  | P4ssW0rt!!!1                         |
      | registration[plainPassword][second] | P4ssW0rt!!!1                         |
    And I press "Submit"

    Then I should be on "/register"
    And I should see "The username must contain at most 32 characters."

  @registration
  Scenario: Register with a plus sign
    When I am on "/register"
    And I fill in the following:
      | registration[voucher]               | TEST         |
      | registration[email]                 | user+test    |
      | registration[plainPassword][first]  | P4ssW0rt!!!1 |
      | registration[plainPassword][second] | P4ssW0rt!!!1 |
    And I press "Submit"

    Then I should be on "/register"
    And I should see "The username contains unexpected characters. Only valid: Letters and numbers, as well as -, _ and ."
