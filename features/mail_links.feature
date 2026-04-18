Feature: Mail links

  Notification mails must link to real routes. This flow catches the class
  of drift that bit #97: a mailer translation hardcoding `%app_url%/<path>`
  while the route moves elsewhere.

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following Setting exists:
      | name    | value                      |
      | app_url | https://mail.example.org |
    And the following User exists:
      | email             | password     | roles     | recoverySecretBox                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        | recoveryStartTime |
      | fresh@example.org | passwordtest | ROLE_USER | jsMdwp9tMK+6x1wuaWbtW67pJxSCYZTWOGhFL12LVMPAIWnR5Zjoe8pAdkUcg/J/S16xEqAvD6uWaBSw43aUk03TXdGKb1mW67dTSf/1UcG98meaIuyY+RrvhQn7KRKQ97PYb40T9BHP77GQkO0EajaNUBSodQpNDoZ3flZHe5wxfiZs6822HRe1hNtuURv/8sRSQG859ff0w4cdaqcd2hBbo0nQT1wDtjLN7t2rbtUeXemI+1tfMXiEK+wTu22Zkv/LiyZSBrhW8hdZBYri1O4nB4XwFsRILDj6ei7gZkebcoT0YwdZE1KNmKmjOxTjG78UJrCyp0uw+HuI2A3iA3wAbxCTJODkGuMVdJdG0fFF/k5PgAUt2rWrLmQEQs3jJQNKh5uy6bCoVnSmmfaRAWBj7klDgV98PJWr4D+K1ZrWngS/wCO4AuM7NiStGUR3IUZKhfLrAA5KBBva5LOrxyn+u8TVY6K9gaOvKLfl0DIYHKJtntiMRjNvoAHlaCpO9F2VZBjwIOsybVh6Dul+vclFMWNMtm10aHS9fRyk9t0j4rTELCV65ORKWHQLirlyhdUjDpQ/wy867h9aiNP2QfgRrQG3t5Dyh9Xg6b0b+RpqHQ5FJIxsL2ZNm73JoAXYnMbqep0idBXUZkdeOD++ezg7e+qsl6Zkvm6dqj+Cp8UHV0sNY5o0E3rMxZeh79Tu6TxvADNnRPdMnMWPssjppU3jHzdvGEkXViDGN3V2X140cy6RqH79Wg== | -31 days          |

  @mail_links
  Scenario: Recovery notification mail links use app_url plus route paths
    When I am on "/recovery"
    And I fill in the following:
      | recovery_process[email]         | fresh@example.org                    |
      | recovery_process[recoveryToken] | bbde593d-8a9e-4d0e-a3ab-9fdd9f5c3237 |
    And I press "recovery_process[submit]"

    Then I should see text matching "Second step starts at"
    And the last sent mail body should contain "https://mail.example.org/recovery"
    And the last sent mail body should contain "https://mail.example.org/account/recovery-token"
