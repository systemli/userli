@api @roundcube
Feature: Roundcube API
    As a Roundcube webmail client
    I need to retrieve user aliases after authentication
    So that users can send mail from their alias addresses

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
            | user_id | source             | destination       |
            | 2       | alias@example.org  | user2@example.org |
            | 2       | alias2@example.org | user2@example.org |
        And the following ApiToken exists:
            | token              | name            | scopes    |
            | roundcube-test-123 | Roundcube Token | roundcube |

    @aliases
    Scenario: Get user aliases without credentials
        Given I have no API token
        When I send a POST request to "/api/roundcube/aliases"
        Then the response status code should equal 401

    @aliases
    Scenario: Get user aliases with invalid request format
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/aliases" with raw body:
            """
            {"email": "user2@example.org", "password": "password"}
            """
        Then the response status code should equal 400

    @aliases
    Scenario: Get user aliases for user with aliases
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/aliases" with form data:
            | email    | user2@example.org |
            | password | password          |
        Then the response status code should equal 200
        And the JSON response should be:
            """
            ["alias@example.org", "alias2@example.org"]
            """

    @aliases
    Scenario: Get user aliases for user without aliases
        Given I have a valid API token "roundcube-test-123"
        When I send a POST request to "/api/roundcube/aliases" with form data:
            | email    | user@example.org |
            | password | password         |
        Then the response status code should equal 200
        And the JSON response should be:
            """
            []
            """
