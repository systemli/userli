# Roles and permissions

Every account has one or more roles. Roles are set by admins when
[creating or editing a user](users.md).

| Role (in the UI)  | Technical name      | Effect |
|-------------------|---------------------|--------|
| **User**          | `ROLE_USER`         | Default role of every account. |
| **Admin**         | `ROLE_ADMIN`        | Full access to the admin interface, all domains and all settings. |
| **Domain admin**  | `ROLE_DOMAIN_ADMIN` | Manages users, aliases and invite codes of **their own domain**. Includes *Permanent* and *Multiplier*. |
| **Permanent**     | `ROLE_PERMANENT`    | The account is never removed as inactive. |
| **Multiplier**    | `ROLE_MULTIPLIER`   | Can create invite codes at any time, without limit and waiting period. |
| **Suspicious**    | `ROLE_SUSPICIOUS`   | No invite codes; already issued codes of this user become invalid. |
| **Spam**          | `ROLE_SPAM`         | Account is restricted: can read mails, but only send to the support address. Includes *Suspicious*. |

## Spam

Use *Spam* when an account is compromised or sends spam.

* SMTP authentication (Postfix), IMAP/POP3 login (Dovecot) and webmail login
  (Roundcube) still work. The user can read emails.
* The user can only send emails to the
  [support address](settings.md#email-configuration). Mails to any other
  recipient are rejected. Without a support address, no sending is possible.
  This needs the [Postfix adapter](../operations/integrations/postfix.md#sending-restrictions).
* Incoming emails are still delivered to the mailbox.
* When the role is added, Userli sends the user an email (in English and
  German) that the account is restricted and that they should contact the
  support. The mail contains the support address, if one is set.
* The user can sign in to the web interface but only sees an
  *"Account restricted"* page that says the account is flagged as spammer or
  compromised, with a request to contact support. Aliases and OpenPGP keys
  can't be managed.
* Single sign-on via Keycloak is still refused.
* All effects of *Suspicious* apply, too.

To unlock the account, remove the role. Consider requiring a password change
at the same time.

## Suspicious

Use *Suspicious* when an account invites spammers or abusers.

* The user doesn't get invite codes anymore and the invite code page is hidden.
* Unused invite codes of this user can't be used for registration anymore.
* When the role is added, Userli emails a list of all accounts that registered
  with this user's invite codes to the notification address (see
  [Settings](settings.md#email-configuration)). Review them, too.

Accounts with *Suspicious* or *Spam* are marked in the user search.

## Multiplier

Multipliers see a **Create invite code** button on their invite code page and
can create as many codes as they want – as long as invitations are enabled for
their domain. Use this for people who onboard many users, for example the
organizers of a group.

## Permanent

Userli regularly removes accounts that were not used for about two years (see
[Maintenance](maintenance.md)). Accounts with *Permanent*, *Domain admin* or
*Admin* are skipped. Use it for role or function accounts that nobody signs in
to.

## Admins and domain admins

|                                            | Admin | Domain admin |
|--------------------------------------------|:-----:|:------------:|
| Users, aliases, invite codes, OpenPGP keys | all domains | own domain |
| Manage users with role *Admin*             | ✓     |              |
| Assignable roles                           | all   | User, Domain admin, Permanent |
| Set alias destination and SMTP quota       | ✓     |              |
| Edit invitation settings of a domain       | all domains | own domain |
| Create and delete domains                  | ✓     |              |
| Reserved names, notifications              | ✓     |              |
| Settings, API tokens, webhooks, maintenance | ✓    |              |

The domain of a domain admin is the domain of their own email address.

!!! warning "Domain admins and restricted roles"

    Domain admins can't assign *Spam*, *Suspicious* or *Multiplier*. When a
    domain admin saves a user who has one of these roles, the role may be
    removed. Ask an admin to edit such accounts.

## The first admin

The setup wizard creates the first admin account `postmaster@<domain>`, see
[Initial setup](../operations/initial-setup.md). You can (re)set its password
on the command line:

```shell
bin/console app:admin:password
```
