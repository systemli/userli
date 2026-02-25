@wkd
Feature: Web Key Directory
    As a WKD client
    I need to look up OpenPGP keys by WKD hash
    So that I can discover OpenPGP keys for email addresses

    Background:
        Given the database is clean
        And the following Domain exists:
            | name        |
            | example.org |
        And the following User exists:
            | email             | password |
            | alice@example.org | asdasd   |
        And the following OpenPgpKey exists:
            | email             | user              | keyId | keyFingerprint | keyData    |
            | alice@example.org | alice@example.org | ABC1  | AABBCCDD1234   | dGVzdGtleQ== |

    @wkd-lookup
    Scenario: Lookup existing key by WKD hash
        When I am on "/.well-known/openpgpkey/example.org/hu/kei1q4tipxxu1yj79k9kfukdhfy631xe"
        Then the response status code should be 200
        And the response header "Content-Type" should equal "application/octet-stream"
        And the response header "Access-Control-Allow-Origin" should equal "*"

    @wkd-lookup-not-found
    Scenario: Lookup non-existing key returns 404
        When I am on "/.well-known/openpgpkey/example.org/hu/s9eyq38w8ptio3w48wyoq4jxihjib4is"
        Then the response status code should be 404

    @wkd-lookup-unknown-domain
    Scenario: Lookup key for unknown domain returns 404
        When I am on "/.well-known/openpgpkey/unknown.org/hu/kei1q4tipxxu1yj79k9kfukdhfy631xe"
        Then the response status code should be 404

    @wkd-policy
    Scenario: Policy endpoint returns empty response
        When I am on "/.well-known/openpgpkey/example.org/policy"
        Then the response status code should be 200
        And the response header "Content-Type" should equal "text/plain; charset=UTF-8"
        And the response header "Access-Control-Allow-Origin" should equal "*"

    @wkd-policy-unknown-domain
    Scenario: Policy endpoint works for any domain
        When I am on "/.well-known/openpgpkey/unknown.org/policy"
        Then the response status code should be 200
        And the response header "Content-Type" should equal "text/plain; charset=UTF-8"
