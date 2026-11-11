Feature: Admin - Announcements

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
      | example.com |
    And the following User exists:
      | email              | password | roles             | deleted |
      | louis@example.org  | asdasd   | ROLE_ADMIN        |         |
      | user@example.org   | asdasd   | ROLE_USER         |         |
      | spam@example.org   | asdasd   | ROLE_USER,ROLE_SPAM |       |
      | gone@example.org   | asdasd   | ROLE_USER         | 1       |
      | domain@example.com | asdasd   | ROLE_DOMAIN_ADMIN |         |
      | user@example.com   | asdasd   | ROLE_USER         |         |

  @announcements
  Scenario: Users cannot access announcements
    Given I am authenticated as "user@example.org"
    When I am on "/admin/announcements/"

    Then the response status code should be 403

  @announcements
  Scenario: Admin can list announcements
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/announcements/"

    Then the response status code should be 200
    And I should see "Announcements"
    And I should see "No announcements"

  @announcements
  Scenario: Admin previews, tests and sends an announcement to active users of a domain
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/announcements/create"
    Then the response status code should be 200

    When I set hidden field "announcement_domain" to "1"
    And I fill in "announcement_subject" with "Planned maintenance"
    And I fill in "announcement_body" with "The server will be down on Sunday."
    And I press "Preview"

    Then the response status code should be 200
    And I should see "Preview"
    And I should see "2 users"
    And I should see "This is an official announcement from"

    When I press "Send test to me"
    Then I should see "A test email has been sent to your address."
    And the last sent mail body should contain "The server will be down on Sunday."
    And the last sent mail body should contain "This is an official announcement from"

    When I press "Send to all users"
    Then I should see "The announcement has been queued for sending."
    And I should see "Planned maintenance"

  @announcements
  Scenario: Changed content must be previewed again before sending
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/announcements/create"
    And I set hidden field "announcement_domain" to "1"
    And I fill in "announcement_subject" with "Planned maintenance"
    And I fill in "announcement_body" with "First version"
    And I press "Preview"
    And I fill in "announcement_body" with "Second version"
    And I press "Send to all users"

    Then I should see "The announcement was changed after the preview."
    And I should see "Second version"
    And I should not see "The announcement has been queued for sending."

  @announcements
  Scenario: Empty announcements are rejected
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/announcements/create"
    And I set hidden field "announcement_domain" to "1"
    And I press "Preview"

    Then the response status code should be 422

  @announcements
  Scenario: Domain admin sends announcements only to their own domain
    Given I am authenticated as "domain@example.com"
    When I am on "/admin/announcements/create"

    Then the response status code should be 200
    And the response should not contain "announcement[domain]"

    When I fill in "announcement_subject" with "Hello example.com"
    And I fill in "announcement_body" with "News for our users."
    And I press "Preview"

    Then I should see "example.com"
    And I should see "2 users"

    When I press "Send to all users"
    Then I should see "The announcement has been queued for sending."
    And I should see "Hello example.com"

  @announcements
  Scenario: Domain admin does not see announcements of other domains
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/announcements/create"
    And I set hidden field "announcement_domain" to "1"
    And I fill in "announcement_subject" with "Only for example.org"
    And I fill in "announcement_body" with "Secret"
    And I press "Preview"
    And I press "Send to all users"
    Then I should see "Only for example.org"

    Given I am authenticated as "domain@example.com"
    When I am on "/admin/announcements/"

    Then the response status code should be 200
    And I should not see "Only for example.org"
