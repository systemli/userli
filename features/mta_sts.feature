@mta-sts
Feature: MTA-STS
    As a sending mail server
    I need to fetch the MTA-STS policy
    So that I can enforce TLS for email delivery

    Background:
        Given the database is clean
        And the following Domain exists:
            | name        |
            | example.org |
        And the following Setting exists:
            | name            | value            |
            | mta_sts_mode    | enforce          |
            | mta_sts_mx      | mail.example.org |
            | mta_sts_max_age | 604800           |

    @mta-sts-policy
    Scenario: Fetch MTA-STS policy with valid host
        When I set the host header to "mta-sts.example.org"
        And I am on "/.well-known/mta-sts.txt"
        Then the response status code should be 200
        And the response header "Content-Type" should equal "text/plain; charset=utf-8"
        And the response body should contain "version: STSv1"
        And the response body should contain "mode: enforce"
        And the response body should contain "mx: mail.example.org"
        And the response body should contain "max_age: 604800"

    @mta-sts-policy-unknown-domain
    Scenario: Fetch MTA-STS policy for unknown domain returns 404
        When I set the host header to "mta-sts.unknown.org"
        And I am on "/.well-known/mta-sts.txt"
        Then the response status code should be 404

    @mta-sts-policy-no-prefix
    Scenario: Fetch MTA-STS policy without mta-sts prefix returns 404
        When I set the host header to "example.org"
        And I am on "/.well-known/mta-sts.txt"
        Then the response status code should be 404

    @mta-sts-policy-none
    Scenario: Fetch MTA-STS policy with mode none returns valid policy
        Given the following Setting exists:
            | name         | value |
            | mta_sts_mode | none  |
        When I set the host header to "mta-sts.example.org"
        And I am on "/.well-known/mta-sts.txt"
        Then the response status code should be 200
        And the response body should contain "version: STSv1"
        And the response body should contain "mode: none"
        And the response body should contain "max_age: 604800"

    @mta-sts-policy-no-mx
    Scenario: Fetch MTA-STS policy without MX hosts returns 404
        Given the following Setting exists:
            | name       | value |
            | mta_sts_mx |       |
        When I set the host header to "mta-sts.example.org"
        And I am on "/.well-known/mta-sts.txt"
        Then the response status code should be 404
