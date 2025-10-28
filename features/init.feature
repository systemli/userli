Feature: Initialization

  Background:
    Given the database is clean

  @init
  Scenario: Initialize the installation
    When I am on homepage

    Then I should be on "/init"
    And print last response

    When I fill in the following:
      | create_domain_domain | example.org |
    And I press "create_domain_submit"

    Then I should be on "/init/user"

    When I fill in the following:
      | plain_password[plainPassword][first]  | P4ssW0rt!!!1 |
      | plain_password[plainPassword][second] | P4ssW0rt!!!1 |
    And I press "Submit"

    Then I should be on "/init/settings"

    When I fill in the following:
      | settings[app_name]                   | Example Mail Service     |
      | settings[app_url]                    | https://mail.example.org |
      | settings[project_name]               | Example Project          |
      | settings[project_url]                | https://example.org      |
      | settings[email_sender_address]       | noreply@example.org      |
      | settings[email_notification_address] | admin@example.org        |
    And I press "settings_save"

    Then I should be on "/"

  @init
  Scenario: No more redirect to init site
    When the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email                  | password |
      | postmaster@example.org | P4ssW0rt |
    And the following Setting exists:
      | name     | value                    |
      | app_name | Example Mail Service     |
      | app_url  | https://mail.example.org |
    And I am on homepage

    Then I should be on "/"

  @init
  Scenario: I will redirect when domain and user exists
    When the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email                  | password |
      | postmaster@example.org | P4ssW0rt |
    And the following Setting exists:
      | name     | value                    |
      | app_name | Example Mail Service     |
      | app_url  | https://mail.example.org |
    And I am on "/init"
    Then I should be on "/"

    When I am on "/init/user"
    Then I should be on "/"

    When I am on "/init/settings"
    Then I should be on "/"
