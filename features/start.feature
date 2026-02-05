Feature: Start Page
    As a visitor or user
    I need to see the appropriate start page
    So that I can access the application or my account

    Background:
        Given the database is clean
        And the following Domain exists:
            | name        |
            | example.org |
        And the following User exists:
            | email            | password | roles     |
            | user@example.org | asdasd   | ROLE_USER |
            | spam@example.org | asdasd   | ROLE_SPAM |

    @start
    Scenario: Visit homepage unauthenticated
        When I am on "/"
        Then the response status code should be 200
        And I should see "Welcome to example.org"

    @start
    Scenario: Authenticated user redirects to start page
        Given I am authenticated as "user@example.org"
        When I am on "/"
        Then I should be on "/start"

    @start
    Scenario: Spammer sees locked message
        Given I am authenticated as "spam@example.org"
        When I am on "/start"
        Then the response status code should be 200
        And I should see "Account locked"

    @start
    Scenario: Regular user sees dashboard
        Given I am authenticated as "user@example.org"
        When I am on "/start"
        Then the response status code should be 200
        And I should see "Manage your e-mail account"
