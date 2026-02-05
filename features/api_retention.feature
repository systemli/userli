@api @retention
Feature: Retention API
    As a mail retention service
    I need to track user activity and find inactive users
    So that I can manage mailbox retention policies

    Background:
        Given the database is clean
        And the following Domain exists:
            | name        |
            | example.org |
        And the following User exists:
            | email              | password | roles     |
            | user@example.org   | password | ROLE_USER |
        And the following ApiToken exists:
            | token              | name            | scopes    |
            | retention-test-123 | Retention Token | retention |
            | dovecot-test-456   | Dovecot Token   | dovecot   |

    @touch
    Scenario: Touch user with wrong API token
        Given I have an invalid API token
        When I send a PUT request to "/api/retention/nonexistent@example.org/touch"
        Then the response status code should equal 401

    @touch
    Scenario: Touch user with wrong scope
        Given I have a valid API token "dovecot-test-456"
        When I send a PUT request to "/api/retention/user@example.org/touch"
        Then the response status code should equal 403

    @touch
    Scenario: Touch unknown user
        Given I have a valid API token "retention-test-123"
        When I send a PUT request to "/api/retention/nonexistent@example.org/touch"
        Then the response status code should equal 404

    @touch
    Scenario: Touch user with timestamp in future
        Given I have a valid API token "retention-test-123"
        When I send a PUT request to "/api/retention/user@example.org/touch" with JSON:
            """
            {"timestamp": 999999999999}
            """
        Then the response status code should equal 400
        And the JSON path "message" should equal "timestamp in future"

    @touch
    Scenario: Touch user with valid timestamp
        Given I have a valid API token "retention-test-123"
        When I send a PUT request to "/api/retention/user@example.org/touch" with JSON:
            """
            {"timestamp": 0}
            """
        Then the response status code should equal 200
        And the JSON response should be:
            """
            []
            """

    @touch
    Scenario: Touch user without timestamp
        Given I have a valid API token "retention-test-123"
        When I send a PUT request to "/api/retention/user@example.org/touch" with JSON:
            """
            {}
            """
        Then the response status code should equal 200
        And the JSON response should be:
            """
            []
            """

    @users
    Scenario: Get inactive users with wrong scope
        Given I have a valid API token "dovecot-test-456"
        When I send a GET request to "/api/retention/users"
        Then the response status code should equal 403

    @users
    Scenario: Get inactive users
        Given I have a valid API token "retention-test-123"
        When I send a GET request to "/api/retention/users"
        Then the response status code should equal 200
