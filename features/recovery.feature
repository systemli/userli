Feature: Recovery

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password     | roles     | recoverySecret                                                                                                                                   | recoveryStartTime |
      | user@example.org  | passwordtest | ROLE_USER | KHzfT8ykMi2ECEC+h6kUv2BNURDowQA3P1NazD0kQwsaoqEOGY/Kjxb/f6IimMTFCA8rBNmNoohPqN/irzduZwYcyDIZpzufHG57MflI6B/01PeVBNcyoOC12b5UwsUZI8lms6qcNR59xijV | NOW               |
      | user2@example.org | passwordtest | ROLE_USER | KHzfT8ykMi2ECEC+h6kUv2BNURDowQA3P1NazD0kQwsaoqEOGY/Kjxb/f6IimMTFCA8rBNmNoohPqN/irzduZwYcyDIZpzufHG57MflI6B/01PeVBNcyoOC12b5UwsUZI8lms6qcNR59xijV | -3 days           |

  @recovery
  Scenario: Start recovery process as user (#1)
    When I am on "/recovery"
    And I fill in the following:
      | recovery_process[email]         | user@example.org                     |
      | recovery_process[recoveryToken] | 803f124a-49a8-4c0d-b75d-a3f448614a25 |
    And I press "recovery_process[submit]"

    Then I should be on "/recovery"
    And I should see text matching "Second step starts at"
    And the response status code should be 200

  @recovery
  Scenario: Continue recovery process as user (#2)
    When I am on "/recovery"
    And I fill in the following:
      | recovery_process[email]         | user2@example.org                    |
      | recovery_process[recoveryToken] | 803f124a-49a8-4c0d-b75d-a3f448614a25 |
    And I press "recovery_process[submit]"

    Then I should be on "/recovery"
    And I should see text matching "You're now allowed to reset your password"
    And the response status code should be 200

  @recovery
  Scenario: Reset password in recovery process as user (#3)
    When I have the request params for "recovery_reset_password":
      | email         | user2@example.org                    |
      | recoveryToken | 803f124a-49a8-4c0d-b75d-a3f448614a25 |
    And I request "POST /recovery/reset_password"
    And I fill in the following:
      | recovery_reset_password[newPassword][first]  | passwordabcd                         |
      | recovery_reset_password[newPassword][second] | passwordabcd                         |
    And I press "recovery_reset_password[submit]"

#    Then print last response
    Then I should be on "/login"
    And I should see text matching "You successfully changed your password. Go on with the login."
    And the response status code should be 200

  @recovery
  Scenario: Recovery authentication failures
    When I am on "/recovery"
    And I fill in the following:
      | recovery_process[email]         | nonexistent@example.org              |
      | recovery_process[recoveryToken] | 803f124a-49a8-4c0d-b75d-a3f448614a25 |
    And I press "recovery_process[submit]"

    Then I should be on "/recovery"
    And I should see text matching "Email address and/or recovery token are wrong!"

  @recovery
  Scenario: Recovery invalid email
    When I am on "/recovery"
    And I fill in the following:
      | recovery_process[email]         | user                                 |
      | recovery_process[recoveryToken] | 803f124a-49a8-4c0d-b75d-a3f448614a25 |
    And I press "recovery_process[submit]"

    Then I should be on "/recovery"
    And I should see text matching "This value is not a valid email address."

  @recovery
  Scenario: Recovery invalid UUID
    When I am on "/recovery"
    And I fill in the following:
      | recovery_process[email]         | user@example.org |
      | recovery_process[recoveryToken] | broken_token     |
    And I press "recovery_process[submit]"

    Then I should be on "/recovery"
    And I should see text matching "This token has an invalid format."
