Feature: Invitation settings per domain

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        | invitation_enabled | invitation_limit |
      | example.org | 1                  | 3                |
    And the following User exists:
      | email             | password | roles      |
      | admin@example.org | asdasd   | ROLE_ADMIN |
      | user@example.org  | asdasd   | ROLE_USER  |
    And the following Voucher exists:
      | code   | user              |
      | TEST00 | admin@example.org |

  @invitation
  Scenario: Voucher menu item is visible for users on domain with invitations enabled
    When I am authenticated as "user@example.org"
    And I am on "/account"

    Then I should see "Invite codes"

  @invitation
  Scenario: Voucher menu item is not visible for users on domain with invitations disabled
    Given the following Domain exists:
      | name         | invitation_enabled | invitation_limit |
      | disabled.org | 0                  | 0                |
    And the following User exists:
      | email             | password | roles     |
      | user@disabled.org | asdasd   | ROLE_USER |

    When I am authenticated as "user@disabled.org"
    And I am on "/account"

    Then I should not see "Invite codes"

  @invitation
  Scenario: Voucher page shows disabled message for domain with invitations disabled
    Given the following Domain exists:
      | name         | invitation_enabled | invitation_limit |
      | disabled.org | 0                  | 0                |
    And the following User exists:
      | email             | password | roles     |
      | user@disabled.org | asdasd   | ROLE_USER |

    When I am authenticated as "user@disabled.org"
    And I am on "/account/voucher"

    Then I should see "Invitations Disabled"

  @invitation
  Scenario: Registration with voucher from domain with invitations disabled is rejected
    Given the following Domain exists:
      | name         | invitation_enabled | invitation_limit |
      | disabled.org | 0                  | 0                |
    And the following User exists:
      | email             | password | roles     |
      | user@disabled.org | asdasd   | ROLE_USER |
    And the following Voucher exists:
      | code   | user              |
      | DIS000 | user@disabled.org |

    When I am on "/register/DIS000"

    Then I should be on "/register/DIS000"
    And I should see "Registration closed"

  @invitation
  Scenario: Admin can edit invitation settings for a domain
    When I am authenticated as "admin@example.org"
    And I am on "/admin/domains/"
    And I follow "Edit"

    Then the response status code should be 200
    And I should see "Enable Invitations"
    And I should see "Initial Invitations per User"

    When I fill in "domain_edit[invitationLimit]" with "5"
    And I press "Save Changes"

    Then I should see "Domain settings have been updated successfully"
