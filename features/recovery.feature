Feature: Recovery

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password     | roles     | recoverySecretBox                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        | recoveryStartTime |
      | user@example.org  | passwordtest | ROLE_USER | jsMdwp9tMK+6x1wuaWbtW67pJxSCYZTWOGhFL12LVMPAIWnR5Zjoe8pAdkUcg/J/S16xEqAvD6uWaBSw43aUk03TXdGKb1mW67dTSf/1UcG98meaIuyY+RrvhQn7KRKQ97PYb40T9BHP77GQkO0EajaNUBSodQpNDoZ3flZHe5wxfiZs6822HRe1hNtuURv/8sRSQG859ff0w4cdaqcd2hBbo0nQT1wDtjLN7t2rbtUeXemI+1tfMXiEK+wTu22Zkv/LiyZSBrhW8hdZBYri1O4nB4XwFsRILDj6ei7gZkebcoT0YwdZE1KNmKmjOxTjG78UJrCyp0uw+HuI2A3iA3wAbxCTJODkGuMVdJdG0fFF/k5PgAUt2rWrLmQEQs3jJQNKh5uy6bCoVnSmmfaRAWBj7klDgV98PJWr4D+K1ZrWngS/wCO4AuM7NiStGUR3IUZKhfLrAA5KBBva5LOrxyn+u8TVY6K9gaOvKLfl0DIYHKJtntiMRjNvoAHlaCpO9F2VZBjwIOsybVh6Dul+vclFMWNMtm10aHS9fRyk9t0j4rTELCV65ORKWHQLirlyhdUjDpQ/wy867h9aiNP2QfgRrQG3t5Dyh9Xg6b0b+RpqHQ5FJIxsL2ZNm73JoAXYnMbqep0idBXUZkdeOD++ezg7e+qsl6Zkvm6dqj+Cp8UHV0sNY5o0E3rMxZeh79Tu6TxvADNnRPdMnMWPssjppU3jHzdvGEkXViDGN3V2X140cy6RqH79Wg== |                   |
      | user2@example.org | passwordtest | ROLE_USER | jsMdwp9tMK+6x1wuaWbtW67pJxSCYZTWOGhFL12LVMPAIWnR5Zjoe8pAdkUcg/J/S16xEqAvD6uWaBSw43aUk03TXdGKb1mW67dTSf/1UcG98meaIuyY+RrvhQn7KRKQ97PYb40T9BHP77GQkO0EajaNUBSodQpNDoZ3flZHe5wxfiZs6822HRe1hNtuURv/8sRSQG859ff0w4cdaqcd2hBbo0nQT1wDtjLN7t2rbtUeXemI+1tfMXiEK+wTu22Zkv/LiyZSBrhW8hdZBYri1O4nB4XwFsRILDj6ei7gZkebcoT0YwdZE1KNmKmjOxTjG78UJrCyp0uw+HuI2A3iA3wAbxCTJODkGuMVdJdG0fFF/k5PgAUt2rWrLmQEQs3jJQNKh5uy6bCoVnSmmfaRAWBj7klDgV98PJWr4D+K1ZrWngS/wCO4AuM7NiStGUR3IUZKhfLrAA5KBBva5LOrxyn+u8TVY6K9gaOvKLfl0DIYHKJtntiMRjNvoAHlaCpO9F2VZBjwIOsybVh6Dul+vclFMWNMtm10aHS9fRyk9t0j4rTELCV65ORKWHQLirlyhdUjDpQ/wy867h9aiNP2QfgRrQG3t5Dyh9Xg6b0b+RpqHQ5FJIxsL2ZNm73JoAXYnMbqep0idBXUZkdeOD++ezg7e+qsl6Zkvm6dqj+Cp8UHV0sNY5o0E3rMxZeh79Tu6TxvADNnRPdMnMWPssjppU3jHzdvGEkXViDGN3V2X140cy6RqH79Wg== | -3 days           |

  @recovery
  Scenario: Start recovery process as user (#1)
    When I am on "/recovery"
    Then I should see text matching "Recovery takes two visits"
    And the "li[aria-current='step']" element should contain "Start"

    When I fill in the following:
      | recovery_process[email]         | user@example.org                     |
      | recovery_process[recoveryToken] | bbde593d-8a9e-4d0e-a3ab-9fdd9f5c3237 |
    And I press "recovery_process[submit]"

    Then I should be on "/recovery"
    And I should see text matching "You can set a new password from"
    And the "li[aria-current='step']" element should contain "Wait"
    And the response status code should be 200
    And I should see text matching "Recovery started"

    When I am on "/recovery"
    And I fill in the following:
      | recovery_process[email]         | user@example.org                     |
      | recovery_process[recoveryToken] | bbde593d-8a9e-4d0e-a3ab-9fdd9f5c3237 |
    And I press "recovery_process[submit]"

    Then I should be on "/recovery"
    And I should see text matching "Recovery is already in progress"
    And I should see text matching "Time left: 4[78] h"
    And the "li[aria-current='step']" element should contain "Wait"

  @recovery
  Scenario: Continue recovery process as user (#2)
    When I am on "/recovery"
    And I fill in the following:
      | recovery_process[email]         | user2@example.org                    |
      | recovery_process[recoveryToken] | bbde593d-8a9e-4d0e-a3ab-9fdd9f5c3237 |
    And I press "recovery_process[submit]"

    Then I should be on "/recovery/reset_password"
    And I should see text matching "You're now allowed to reset your password"
    And the "li[aria-current='step']" element should contain "New password"
    And the response status code should be 200

  @recovery
  Scenario: Reload password reset page in recovery process
    When I am on "/recovery"
    And I fill in the following:
      | recovery_process[email]         | user2@example.org                    |
      | recovery_process[recoveryToken] | bbde593d-8a9e-4d0e-a3ab-9fdd9f5c3237 |
    And I press "recovery_process[submit]"
    And I reload the page

    Then I should be on "/recovery/reset_password"
    And I should see text matching "You're now allowed to reset your password"
    And the response status code should be 200

    When I fill in the following:
      | recovery_reset_password[password][first]  | XV7FxQWj9dApApN |
      | recovery_reset_password[password][second] | XV7FxQWj9dApApN |
    And I press "recovery_reset_password[submit]"

    Then I should be on "/recovery/recovery_token/ack"
    And I should see text matching "You changed your password."

    When I am on "/recovery/reset_password"

    Then I should be on "/recovery"
    And I should see text matching "Please enter your e-mail address and recovery token again"

  @recovery
  Scenario: Open password reset page without authentication
    When I am on "/recovery/reset_password"

    Then I should be on "/recovery"
    And I should see text matching "Please enter your e-mail address and recovery token again"
    And the response status code should be 200

  @recovery
  Scenario: Reset password in recovery process as user (#3)
    When I have the request params for "recovery_reset_password":
      | email         | user2@example.org                    |
      | recoveryToken | bbde593d-8a9e-4d0e-a3ab-9fdd9f5c3237 |
    And I request "POST /recovery/reset_password"
    And I fill in the following:
      | recovery_reset_password[password][first]  | XV7FxQWj9dApApN |
      | recovery_reset_password[password][second] | XV7FxQWj9dApApN |
    And I press "recovery_reset_password[submit]"

    Then I should be on "/recovery/recovery_token/ack"
    And I should see text matching "You changed your password."
    And the response status code should be 200

  @recovery
  Scenario: Acknowledge new recovery token in recovery process as user (#4)
    When I am on "/recovery"
    And I fill in the following:
      | recovery_process[email]         | user2@example.org                    |
      | recovery_process[recoveryToken] | bbde593d-8a9e-4d0e-a3ab-9fdd9f5c3237 |
    And I press "recovery_process[submit]"
    And I fill in the following:
      | recovery_reset_password[password][first]  | XV7FxQWj9dApApN |
      | recovery_reset_password[password][second] | XV7FxQWj9dApApN |
    And I press "recovery_reset_password[submit]"

    Then I should be on "/recovery/recovery_token/ack"
    And I should see text matching "Please copy and securely store this recovery token"
    And I should see a "button[data-action='download#save']" element
    And the "li[aria-current='step']" element should contain "Save token"

    When I press "recovery_token_confirm[submit]"

    Then I should be on "/recovery/recovery_token/ack"
    And I should see text matching "Please copy and securely store this recovery token"

    When I check "recovery_token_confirm[confirm]"
    And I press "recovery_token_confirm[submit]"

    Then I should be on "/account"
    And I should see text matching "You generated a recovery token."
    And I should see text matching "Two-factor authentication is off"
    And the response status code should be 200

    When I am on "/recovery/recovery_token/ack"

    Then I should be on "/account"

  @recovery
  Scenario: Open new recovery token page without recovery process
    When I am on "/recovery/recovery_token/ack"

    Then I should be on "/login"

  @recovery
  Scenario: Recovery authentication failures
    When I am on "/recovery"
    And I fill in the following:
      | recovery_process[email]         | nonexistent@example.org              |
      | recovery_process[recoveryToken] | bbde593d-8a9e-4d0e-a3ab-9fdd9f5c3237 |
    And I press "recovery_process[submit]"

    Then I should be on "/recovery"
    And the response status code should be 401
    And I should see text matching "The e-mail address or recovery token is not correct."

  @recovery
  Scenario: Reset password with invalid password in recovery process
    When I have the request params for "recovery_reset_password":
      | email         | user2@example.org                    |
      | recoveryToken | bbde593d-8a9e-4d0e-a3ab-9fdd9f5c3237 |
    And I request "POST /recovery/reset_password"
    And I fill in the following:
      | recovery_reset_password[password][first]  | password |
      | recovery_reset_password[password][second] | password |
    And I press "recovery_reset_password[submit]"

    Then I should be on "/recovery/reset_password"
    And I should see "The password comply not with our security policy."

  @recovery
  Scenario: Reset password with wrong recovery token
    When I have the request params for "recovery_reset_password":
      | email         | user2@example.org                    |
      | recoveryToken | 11111111-1111-4111-8111-111111111111 |
    And I request "POST /recovery/reset_password"

    Then I should be on "/recovery"
    And I should see text matching "Your password recovery could not be verified."

  @recovery
  Scenario: Recovery invalid UUID
    When I am on "/recovery"
    And I fill in the following:
      | recovery_process[email]         | user@example.org |
      | recovery_process[recoveryToken] | broken_token     |
    And I press "recovery_process[submit]"

    Then I should be on "/recovery"
    And I should see text matching "This token has an invalid format."
