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

  @settings
  Scenario: Normal user cannot access settings page
    Given I am authenticated as "user@example.org"
    When I am on "/settings"

    Then I should see "Access Denied"
    And the response status code should be 403

  @settings
  Scenario: Normal user cannot access API settings page
    Given I am authenticated as "user@example.org"
    When I am on "/settings/api"

    Then I should see "Access Denied"
    And the response status code should be 403

  @settings
  Scenario: Admin user can access main settings page
    Given I am authenticated as "louis@example.org"
    When I am on "/settings"

    Then I should see "Settings"
    And I should see "Settings Overview"
    And the response status code should be 200

  @apitokens
  Scenario: Admin user can access API settings page
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/api"

    Then I should see "API Tokens"
    And the response status code should be 200

  @apitokens
  Scenario: Admin user can create a new API token
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/api/create"
    Then I should see "New API Token"

    When I fill in "My new Token" for "api_token_name"
    And I check "keycloak"
    And I check "dovecot"
    And I check "postfix"
    And I press "Create"

    Then I should see "New API token created"

  @webhooks
  Scenario: Admins can list webhooks
    When I am authenticated as "louis@example.org"
    And I am on "/settings/webhooks/"

    Then the response status code should be 200
    And I should see text matching "Create Webhook"

  @webhooks
  Scenario: Users can not list webhooks
    When I am authenticated as "user@example.org"
    And I am on "/settings/webhooks/"

    Then the response status code should be 403

  @webhooks
  Scenario: Admins can create a webhook
    When I am authenticated as "louis@example.org"
    And I am on "/settings/webhooks/create"

    Then the response status code should be 200

    When I fill in "webhook_endpoint_url" with "https://example.org/webhook"
    And I fill in "webhook_endpoint_secret" with "secret"
    And I check "user.created"
    And I check "user.deleted"
    And I press "Create"

    Then I should see "Webhook created successfully"

  @webhooks
  Scenario: Admins can edit a webhook
    Given the following WebhookEndpoint exists:
      | url                      | secret | events       |
      | https://example.org/hook | secret | user.created |

    When I am authenticated as "louis@example.org"
    And I am on "/settings/webhooks/"

    Then the response status code should be 200

    When I follow "Edit"

    Then I should see "Edit Webhook"

    When I fill in "webhook_endpoint_url" with "https://example.org/newhook"
    And I fill in "webhook_endpoint_secret" with "newsecret"
    And I check "user.deleted"
    And I press "Save"

    Then I should see "Webhook updated successfully"

  @webhooks
  Scenario: Admin can list deliveries for a webhook
    Given the following WebhookEndpoint exists:
      | url                      | secret | events       |
      | https://example.org/hook | secret | user.created |

    Given the following WebhookDelivery exists:
      | endpoint_id | type         | request_headers                                                       | request_body                      | response_body         | response_code |
      | 1           | user.created | {"Content-Type":"application/json","X-Webhook-Signature":"signature"} | {"type":"user.created","data":{}} | Success               | 200           |
      | 1           | user.created | {"Content-Type":"application/json","X-Webhook-Signature":"signature"} | {"type":"user.created","data":{}} | Internal Server Error | 500           |

    When I am authenticated as "louis@example.org"
    And I am on "/settings/webhooks/"
    Then the response status code should be 200

    When I follow "Deliveries"

    Then I should see "Webhook Deliveries"
    And the response status code should be 200
    And I should see "Delivered"
    And I should see "Failed"

  @webhooks
  Scenario: Admin can view a delivery for a webhook
    Given the following WebhookEndpoint exists:
      | url                      | secret | events       |
      | https://example.org/hook | secret | user.created |

    Given the following WebhookDelivery exists:
      | endpoint_id | type         | request_headers                                                       | request_body                      | response_body | response_code |
      | 1           | user.created | {"Content-Type":"application/json","X-Webhook-Signature":"signature"} | {"type":"user.created","data":{}} | Success       | 200           |

    When I am authenticated as "louis@example.org"
    And I am on "/settings/webhooks/1/deliveries"
    Then the response status code should be 200

    When I follow "Details"

    Then I should see "Detailed information about this webhook delivery"

  @maintenance
  Scenario: Admin can access maintenance page
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/maintenance"

    Then I should see "Maintenance"
    And the response status code should be 200

  @maintenance
  Scenario: User cannot access maintenance page
    Given I am authenticated as "user@example.org"
    When I am on "/settings/maintenance"

    Then I should see "Access Denied"
    And the response status code should be 403

  @maintenance
  Scenario: Admin can trigger maintenance tasks
    Given I am authenticated as "louis@example.org"
    When I am on "/settings/maintenance"

    Then I should see "Maintenance"
    And the response status code should be 200

    When I press "Unlink vouchers"

    Then I should see "Maintenance task dispatched successfully."
