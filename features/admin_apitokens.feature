Feature: Settings (API Tokens)

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password | roles      |
      | louis@example.org | asdasd   | ROLE_ADMIN |
      | user@example.org  | asdasd   | ROLE_USER  |

  @apitokens
  Scenario: Normal user cannot access API settings page
    Given I am authenticated as "user@example.org"
    When I am on "/admin/api"

    Then I should see "Access Denied"
    And the response status code should be 403

  @apitokens
  Scenario: Admin user can access API settings page
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/api"

    Then I should see "API Tokens"
    And the response status code should be 200

  @apitokens
  Scenario: Admin user can create a new API token
    Given I am authenticated as "louis@example.org"
    When I am on "/admin/api/create"
    Then I should see "New API Token"

    When I fill in "My new Token" for "api_token_name"
    And I check "keycloak"
    And I check "dovecot"
    And I check "postfix"
    And I press "Create"

    Then I should see "New API token created"

  @javascript @apitokens @delete-modal
  Scenario: Delete API token via confirmation modal
    Given the following ApiToken exists:
      | token      | name       | scopes   |
      | test-token | Test Token | keycloak |
    And I am authenticated as "louis@example.org"
    When I am on "/admin/api"
    Then I should see "Test Token"

    When I press "Delete"
    And I wait for the modal to appear
    Then I should see "Confirm deletion" in the modal

    When I click "Cancel" in the modal
    And I wait for the modal to close
    Then I should see "Test Token"

    When I press "Delete"
    And I wait for the modal to appear
    When I click "Delete" in the modal

    Then I should see "API token deleted successfully"
