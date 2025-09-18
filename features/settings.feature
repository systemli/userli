Feature: Settings

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password | roles      |
      | louis@example.org | asdasd   | ROLE_ADMIN |
      | user@example.org  | asdasd   | ROLE_USER  |

  @settings @access @admin
  Scenario: Admin can access settings page
    Given I am authenticated as "louis@example.org"
    When I am on "/settings"
    Then I should see "Settings"
    And I should see "Application Settings"
    And the response status code should be 200

  @settings @access @user
  Scenario: Regular user cannot access settings page
    Given I am authenticated as "user@example.org"
    When I am on "/settings"
    Then the response status code should be 403

  @settings @form @display
  Scenario: Settings form displays all configured fields
    Given I am authenticated as "louis@example.org"
    When I am on "/settings"
    Then I should see "Application Name"
    And I should see "Application URL"
    And I should see "Project Name"
    And I should see "Project URL"
    And I should see "Sender Email Address"
    And I should see "Notification Email Address"
    And I should see "Save Settings"
    And the "settings[app_name]" field should contain "Userli"

  @settings @form @update
  Scenario: Admin can update settings successfully
    Given I am authenticated as "louis@example.org"

    When I am on "/settings"
    And I fill in "settings[app_name]" with "My Custom App"
    And I fill in "settings[email_sender_address]" with "sender@example.org"
    And I press "Save Settings"

    Then I should see "Settings have been updated successfully"
    And the "settings[app_name]" field should contain "My Custom App"
    And the "settings[email_sender_address]" field should contain "sender@example.org"
