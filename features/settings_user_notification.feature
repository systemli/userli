Feature: Settings - User Notifications

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password | roles      |
      | louis@example.org | asdasd   | ROLE_ADMIN |
      | user@example.org  | asdasd   | ROLE_USER  |

  @user-notifications
  Scenario: Admins can list user notifications
    When I am authenticated as "louis@example.org"
    And I am on "/settings/user-notifications/"

    Then the response status code should be 200
    And I should see "User Notifications"

  @user-notifications
  Scenario: Users can not list user notifications
    When I am authenticated as "user@example.org"
    And I am on "/settings/user-notifications/"

    Then the response status code should be 403

  @user-notifications
  Scenario: Admin can filter by user email
    Given the following UserNotification exists:
      | email            | type                  |
      | user@example.org | password_compromised  |

    When I am authenticated as "louis@example.org"
    And I am on "/settings/user-notifications/?search=user@example.org"

    Then the response status code should be 200
    And I should see "user@example.org"
    And I should see "password_compromised"

  @user-notifications
  Scenario: Admin can filter by notification type
    Given the following UserNotification exists:
      | email            | type                  |
      | user@example.org | password_compromised  |

    When I am authenticated as "louis@example.org"
    And I am on "/settings/user-notifications/?type=password_compromised"

    Then the response status code should be 200
    And I should see "user@example.org"
    And I should see "password_compromised"
