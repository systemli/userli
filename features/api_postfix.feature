@api @postfix
Feature: Postfix API
    As a Postfix mail server
    I need to lookup aliases, domains and mailboxes
    So that I can route mail correctly

    Background:
        Given the database is clean
        And the following Domain exists:
            | name        |
            | example.org |
        And the following User exists:
            | email             | password | roles     |
            | user@example.org  | password | ROLE_USER |
            | user2@example.org | password | ROLE_USER |
        And the following Alias exists:
            | user_id | source            | destination       |
            | 2       | alias@example.org | user2@example.org |
        And the following ApiToken exists:
            | token            | name          | scopes  |
            | postfix-test-123 | Postfix Token | postfix |

    @alias
    Scenario: Get alias users with wrong API token
        Given I have an invalid API token
        When I send a GET request to "/api/postfix/alias/alias@example.org"
        Then the response status code should equal 401

    @alias
    Scenario: Get alias users for existing alias
        Given I have a valid API token "postfix-test-123"
        When I send a GET request to "/api/postfix/alias/alias@example.org"
        Then the response status code should equal 200
        And the JSON response should be:
            """
            ["user2@example.org"]
            """

    @alias
    Scenario: Get alias users for non-alias address
        Given I have a valid API token "postfix-test-123"
        When I send a GET request to "/api/postfix/alias/user@example.org"
        Then the response status code should equal 200
        And the JSON response should be:
            """
            []
            """

    @domain
    Scenario: Get domain with wrong API token
        Given I have an invalid API token
        When I send a GET request to "/api/postfix/domain/example.org"
        Then the response status code should equal 401

    @domain
    Scenario: Get existing domain
        Given I have a valid API token "postfix-test-123"
        When I send a GET request to "/api/postfix/domain/example.org"
        Then the response status code should equal 200
        And the JSON response should be:
            """
            true
            """

    @mailbox
    Scenario: Get mailbox with wrong API token
        Given I have an invalid API token
        When I send a GET request to "/api/postfix/mailbox/user@example.org"
        Then the response status code should equal 401

    @mailbox
    Scenario: Get mailbox for existing user
        Given I have a valid API token "postfix-test-123"
        When I send a GET request to "/api/postfix/mailbox/user@example.org"
        Then the response status code should equal 200
        And the JSON response should be:
            """
            true
            """

    @senders
    Scenario: Get senders with wrong API token
        Given I have an invalid API token
        When I send a GET request to "/api/postfix/senders/user@example.org"
        Then the response status code should equal 401

    @senders
    Scenario: Get senders for user without aliases
        Given I have a valid API token "postfix-test-123"
        When I send a GET request to "/api/postfix/senders/user@example.org"
        Then the response status code should equal 200
        And the JSON response should be:
            """
            ["user@example.org"]
            """

    @senders
    Scenario: Get senders for user with alias
        Given I have a valid API token "postfix-test-123"
        When I send a GET request to "/api/postfix/senders/user2@example.org"
        Then the response status code should equal 200
        And the JSON response should be:
            """
            ["user2@example.org"]
            """

    @quota
    Scenario: Get SMTP quota with wrong API token
        Given I have an invalid API token
        When I send a GET request to "/api/postfix/smtp_quota/user@example.org"
        Then the response status code should equal 401

    @quota
    Scenario: Get SMTP quota for non-existent address
        Given I have a valid API token "postfix-test-123"
        When I send a GET request to "/api/postfix/smtp_quota/nonexistent@example.org"
        Then the response status code should equal 404

    @quota
    Scenario: Get SMTP quota for existing user
        Given I have a valid API token "postfix-test-123"
        When I send a GET request to "/api/postfix/smtp_quota/user@example.org"
        Then the response status code should equal 200
        And the JSON path "per_hour" should equal "0"
        And the JSON path "per_day" should equal "0"

    @quota
    Scenario: Get SMTP quota for existing alias
        Given I have a valid API token "postfix-test-123"
        When I send a GET request to "/api/postfix/smtp_quota/alias@example.org"
        Then the response status code should equal 200
        And the JSON path "per_hour" should equal "0"
        And the JSON path "per_day" should equal "0"
