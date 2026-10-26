# Security

Userli manages credentials and encryption keys. This page collects the
security-relevant points for operators.

## Reporting vulnerabilities

Please report security issues privately as described in
[`SECURITY.md`](https://github.com/systemli/userli/blob/main/SECURITY.md), not
in public issues.

## HTTPS

Always serve Userli, `mta-sts.<domain>` and `openpgpkey.<domain>` via HTTPS
only and enable HSTS (see [Webserver](webserver.md)). The Dovecot and Postfix
adapters send user passwords to Userli – use HTTPS for these connections, too,
or keep them on a trusted local network.

## Reverse proxies

If Userli runs behind a reverse proxy or load balancer, tell it which proxies
to trust. Otherwise, Userli sees the proxy's IP address instead of the client's
and may generate `http://` links:

```text
SYMFONY_TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8
```

`REMOTE_ADDR` trusts the directly connected client – only use it if Userli
can't be reached without passing the proxy. The trusted headers can be set with
`SYMFONY_TRUSTED_HEADERS`. See
[Symfony: How to configure Symfony to work behind a load balancer or a reverse proxy](https://symfony.com/doc/current/deployment/proxies.html).

## Brute-force protection

Userli doesn't limit the number of login attempts. Protect the login and API
endpoints at the webserver or with a tool like
[fail2ban](https://github.com/fail2ban/fail2ban) or
[CrowdSec](https://www.crowdsec.net/):

* `POST /login` – web login
* `POST /2fa_check` – two-factor codes
* `POST /recovery` – recovery tokens
* `POST /recovery/reset_password` – recovery tokens during password reset

Failed attempts on these endpoints answer with HTTP `401`, successful ones with
a redirect (`302`). Match on `POST` requests with status `401` in the access
log, for example with the generic CrowdSec `http-generic-bf` scenario.

Failed SMTP and IMAP logins are visible in the Postfix and Dovecot logs.

## API tokens

* Create one [API token](integrations/index.md#api-tokens) per service with
  only the scopes it needs.
* Tokens are stored hashed. Store the plain tokens like passwords.
* Restrict access to `/api/` to the IP addresses of your mail servers in the
  webserver, if possible.

## Secrets

* `APP_SECRET` signs CSRF tokens and queued messages. Keep it secret and back
  it up.
* Restrict file permissions of `.env.local` to the Userli user.

## Passwords and accounts

* Passwords need at least 12 characters and are checked against
  [Have I Been Pwned](https://haveibeenpwned.com/Passwords) on registration
  and change, and regularly on login (see
  [Notifications](../admin/notifications.md)). Only a hash prefix leaves the
  server. Userli needs outgoing HTTPS access to `api.pwnedpasswords.com` for
  this.
* Password recovery has a 48-hour waiting period and notifies the user by
  email.
* Use [roles](../admin/roles.md) *Spam* and *Suspicious* to stop abuse
  quickly.

## Content Security Policy

Userli sends a strict Content Security Policy and other security headers. If
you add custom content (for example in [translations](customization.md)), it
must not rely on inline scripts or external resources.
