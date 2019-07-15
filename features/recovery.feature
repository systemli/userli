Feature: Recovery

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email             | password     | roles     | recoverySecretBox | recoveryStartTime |
      | user@example.org  | passwordtest | ROLE_USER | jsMdwp9tMK+6x1wuaWbtW67pJxSCYZTWOGhFL12LVMPAIWnR5Zjoe8pAdkUcg/J/S16xEqAvD6uWaBSw43aUk03TXdGKb1mW67dTSf/1UcG98meaIuyY+RrvhQn7KRKQ97PYb40T9BHP77GQkO0EajaNUBSodQpNDoZ3flZHe5wxfiZs6822HRe1hNtuURv/8sRSQG859ff0w4cdaqcd2hBbo0nQT1wDtjLN7t2rbtUeXemI+1tfMXiEK+wTu22Zkv/LiyZSBrhW8hdZBYri1O4nB4XwFsRILDj6ei7gZkebcoT0YwdZE1KNmKmjOxTjG78UJrCyp0uw+HuI2A3iA3wAbxCTJODkGuMVdJdG0fFF/k5PgAUt2rWrLmQEQs3jJQNKh5uy6bCoVnSmmfaRAWBj7klDgV98PJWr4D+K1ZrWngS/wCO4AuM7NiStGUR3IUZKhfLrAA5KBBva5LOrxyn+u8TVY6K9gaOvKLfl0DIYHKJtntiMRjNvoAHlaCpO9F2VZBjwIOsybVh6Dul+vclFMWNMtm10aHS9fRyk9t0j4rTELCV65ORKWHQLirlyhdUjDpQ/wy867h9aiNP2QfgRrQG3t5Dyh9Xg6b0b+RpqHQ5FJIxsL2ZNm73JoAXYnMbqep0idBXUZkdeOD++ezg7e+qsl6Zkvm6dqj+Cp8UHV0sNY5o0E3rMxZeh79Tu6TxvADNnRPdMnMWPssjppU3jHzdvGEkXViDGN3V2X140cy6RqH79Wg== | NOW               |
      | user2@example.org | passwordtest | ROLE_USER | jsMdwp9tMK+6x1wuaWbtW67pJxSCYZTWOGhFL12LVMPAIWnR5Zjoe8pAdkUcg/J/S16xEqAvD6uWaBSw43aUk03TXdGKb1mW67dTSf/1UcG98meaIuyY+RrvhQn7KRKQ97PYb40T9BHP77GQkO0EajaNUBSodQpNDoZ3flZHe5wxfiZs6822HRe1hNtuURv/8sRSQG859ff0w4cdaqcd2hBbo0nQT1wDtjLN7t2rbtUeXemI+1tfMXiEK+wTu22Zkv/LiyZSBrhW8hdZBYri1O4nB4XwFsRILDj6ei7gZkebcoT0YwdZE1KNmKmjOxTjG78UJrCyp0uw+HuI2A3iA3wAbxCTJODkGuMVdJdG0fFF/k5PgAUt2rWrLmQEQs3jJQNKh5uy6bCoVnSmmfaRAWBj7klDgV98PJWr4D+K1ZrWngS/wCO4AuM7NiStGUR3IUZKhfLrAA5KBBva5LOrxyn+u8TVY6K9gaOvKLfl0DIYHKJtntiMRjNvoAHlaCpO9F2VZBjwIOsybVh6Dul+vclFMWNMtm10aHS9fRyk9t0j4rTELCV65ORKWHQLirlyhdUjDpQ/wy867h9aiNP2QfgRrQG3t5Dyh9Xg6b0b+RpqHQ5FJIxsL2ZNm73JoAXYnMbqep0idBXUZkdeOD++ezg7e+qsl6Zkvm6dqj+Cp8UHV0sNY5o0E3rMxZeh79Tu6TxvADNnRPdMnMWPssjppU3jHzdvGEkXViDGN3V2X140cy6RqH79Wg== | -3 days           |

  @recovery
  Scenario: Start recovery process as user (#1)
    When I am on "/recovery"
    And I fill in the following:
      | recovery_process[email]         | user@example.org                     |
      | recovery_process[recoveryToken] | bbde593d-8a9e-4d0e-a3ab-9fdd9f5c3237 |
    And I press "recovery_process[submit]"

    Then I should be on "/en/recovery"
    And I should see text matching "Second step starts at"
    And the response status code should be 200

  @recovery
  Scenario: Continue recovery process as user (#2)
    When I am on "/recovery"
    And I fill in the following:
      | recovery_process[email]         | user2@example.org                    |
      | recovery_process[recoveryToken] | bbde593d-8a9e-4d0e-a3ab-9fdd9f5c3237 |
    And I press "recovery_process[submit]"

    Then I should be on "/en/recovery"
    And I should see text matching "You're now allowed to reset your password"
    And the response status code should be 200

  @recovery
  Scenario: Reset password in recovery process as user (#3)
    When I have the request params for "recovery_reset_password":
      | email         | user2@example.org                    |
      | recoveryToken | bbde593d-8a9e-4d0e-a3ab-9fdd9f5c3237 |
    And I request "POST /en/recovery/reset_password"
    And I fill in the following:
      | recovery_reset_password[newPassword][first]  | passwordabcd |
      | recovery_reset_password[newPassword][second] | passwordabcd |
    And I press "recovery_reset_password[submit]"

    Then I should be on "/en/recovery/reset_password"
    And I should see text matching "You changed your password."
    And the response status code should be 200

  @recovery
  Scenario: Acknowledge new recovery token in recovery process as user (#4)
    When I have the request params for "recovery_token_ack":
      | recoveryToken | bbde593d-8a9e-4d0e-a3ab-9fdd9f5c3237 |
    And I request "POST /en/recovery/recovery_token/ack"
    And I fill in the following:
      | recovery_token_ack[ack]           | 1                                    |
    And I press "recovery_token_ack[submit]"

    Then I should be on "/en/login"
    And I should see text matching "Go on with the login."
    And the response status code should be 200

  @recovery
  Scenario: Recovery authentication failures
    When I am on "/recovery"
    And I fill in the following:
      | recovery_process[email]         | nonexistent@example.org              |
      | recovery_process[recoveryToken] | bbde593d-8a9e-4d0e-a3ab-9fdd9f5c3237 |
    And I press "recovery_process[submit]"

    Then I should be on "/en/recovery"
    And I should see text matching "Email address and/or recovery token are wrong!"

  @recovery
  Scenario: Start recovery process with local part only
    When I am on "/recovery"
    And I fill in the following:
      | recovery_process[email]         | user                                 |
      | recovery_process[recoveryToken] | bbde593d-8a9e-4d0e-a3ab-9fdd9f5c3237 |
    And I press "recovery_process[submit]"

    Then I should be on "/en/recovery"
    And I should see text matching "Second step starts at"
    And the response status code should be 200

  @recovery
  Scenario: Recovery invalid UUID
    When I am on "/recovery"
    And I fill in the following:
      | recovery_process[email]         | user@example.org |
      | recovery_process[recoveryToken] | broken_token     |
    And I press "recovery_process[submit]"

    Then I should be on "/en/recovery"
    And I should see text matching "This token has an invalid format."
