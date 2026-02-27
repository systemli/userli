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
    And I check "user.reset"
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

    When I am on "/settings/webhooks/1/deliveries?status=success"
    Then the response status code should be 200
    And I should not see "Retry"

    When I am on "/settings/webhooks/1/deliveries?status=failed"
    Then the response status code should be 200
    And I should see "Retry"

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

  @webhooks
  Scenario: Webhook endpoint without domains shows "All domains"
    Given the following WebhookEndpoint exists:
      | url                      | secret | events       |
      | https://example.org/hook | secret | user.created |

    When I am authenticated as "louis@example.org"
    And I am on "/settings/webhooks/"

    Then the response status code should be 200
    And I should see "All domains"

  @webhooks
  Scenario: Webhook endpoint with domains shows domain name
    Given the following WebhookEndpoint exists:
      | url                      | secret | events       | domains     |
      | https://example.org/hook | secret | user.created | example.org |

    When I am authenticated as "louis@example.org"
    And I am on "/settings/webhooks/"

    Then the response status code should be 200
    And I should see "example.org"
    And I should not see "All domains"
