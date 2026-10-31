# Settings

**System → Settings** (admins only) contains the global settings of the
installation. They are stored in the database and apply immediately. The
basic settings are first set in the [setup wizard](../operations/initial-setup.md#setup-wizard).

![Settings](../assets/images/admin-settings.png){class="screenshot"}

## Application settings

| Setting         | Default                     | Description |
|-----------------|-----------------------------|-------------|
| **App name**    | `Userli`                    | Name in the header, browser title and start page (3–30 characters). |
| **App URL**     | `https://users.example.org` | Public URL of Userli. Used in links in emails and invite links. |
| **Webmail URL** | –                           | URL of your webmail. Shows a *Webmail* card to users. Leave empty to hide it. |

## Project settings

| Setting          | Default                  | Description |
|------------------|--------------------------|-------------|
| **Project name** | `example.org`            | Name of your project or organization, used on the welcome page and in emails. |
| **Project URL**  | `https://www.example.org` | Website of your project. |

## Email configuration

| Setting                        | Default                  | Description |
|--------------------------------|--------------------------|-------------|
| **Sender address**             | `admin@example.org`      | Sender of all emails from Userli. |
| **Notification address**       | `monitoring@example.org` | Receives the weekly report and reports about suspicious users. |
| **Support address**            | empty                    | Accounts with the role [*Spam*](roles.md#spam) can only send emails to this address, and see it on their account page. If empty, they can't send any emails. |

## Mailbox encryption

| Setting       | Default | Description |
|---------------|---------|-------------|
| **MailCrypt** | `2`     | `0` disabled, `1` optional, `2` enforce for new users, `3` enforce for all users. |

![MailCrypt settings](../assets/images/admin-settings-mailcrypt.png){class="screenshot"}

See [MailCrypt](../operations/mailcrypt.md) for what the levels mean and how to
migrate existing users. Keep the default unless you know why to change it.

## Two-factor authentication

| Setting                               | Default | Description |
|---------------------------------------|---------|-------------|
| **Require app passwords with 2FA**    | on      | Users with 2FA can sign in to mail programs (IMAP, SMTP) only with [app passwords](../user/app-passwords.md), not with their password. |
| **Security Key Domain**               | empty   | Domain that [security keys](../user/security.md#add-a-security-key) are bound to (WebAuthn RP ID), e.g. `example.org`. Empty means the host of the application URL. |
| **Additional Security Key Origins**   | empty   | Further origins that may use security keys, one per line, e.g. `https://login.example.org` for [Keycloak](../operations/integrations/keycloak.md). The application URL is always allowed. |

Upgraded installations start with this setting off, so mail programs of
existing 2FA users keep working. Tell your users to create app passwords before
you turn it on. Webmail needs the
[Roundcube plugin](../operations/integrations/roundcube.md) to sign in with 2FA.
Users with only security keys sign in to webmail with a backup code.

!!! warning

    Security keys only work on the domain they were added for. The security key
    domain must be the host of the application URL or a parent domain of it, and
    changing it later makes all registered security keys unusable. Set a parent
    domain like `example.org` from the start if other services, for example
    Keycloak on `login.example.org`, should accept the keys too.

## Reports

| Setting           | Default | Description |
|-------------------|---------|-------------|
| **Weekly report** | on      | Every week, send a list of new registrations to the notification address. |

## SMTP quota limits

| Setting      | Default | Description |
|--------------|---------|-------------|
| **Per hour** | `0`     | Default maximum number of emails a user can send per hour. `0` = unlimited. |
| **Per day**  | `0`     | Default maximum number of emails a user can send per day. `0` = unlimited. |

Limits can be overridden per [user](users.md) and per [alias](aliases.md).
Userli only provides the numbers – your mail server has to enforce them, see
[Postfix](../operations/integrations/postfix.md).

## MTA-STS

| Setting     | Default   | Description |
|-------------|-----------|-------------|
| **Mode**    | `testing` | `testing` (report only), `enforce` (reject on failure) or `none` (opt out). |
| **MX hosts** | –        | Allowed MX hostnames, one per line, wildcards like `*.example.org` allowed. |
| **Max age** | `604800`  | How long (in seconds) sending servers cache the policy. 1 day to 1 year. |

![MTA-STS settings](../assets/images/admin-settings-mta-sts.png){class="screenshot"}

No policy is published while the MX list is empty. See
[MTA-STS](../operations/mta-sts.md) for the required DNS records.

## RFC email addresses

| Setting               | Default | Description |
|-----------------------|---------|-------------|
| **Postmaster address** | –      | Destination of `postmaster@` on **all** domains ([RFC 5321](https://datatracker.ietf.org/doc/html/rfc5321)). |
| **Abuse address**     | –       | Destination of `abuse@` on **all** domains ([RFC 2142](https://datatracker.ietf.org/doc/html/rfc2142)). |

If set, these override aliases with the same address. Leave empty to use
aliases instead.
