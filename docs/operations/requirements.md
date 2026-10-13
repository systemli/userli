# Requirements

## Userli host

* A webserver with PHP-FPM, for example [Caddy](https://caddyserver.com/),
  [Nginx](https://nginx.org/) or [Apache](https://httpd.apache.org/)
* [PHP](https://www.php.net/) 8.4 with the extensions
    * `sodium` (mailbox encryption)
    * `intl`
    * `pdo_mysql`
    * `gd`, `zip`, `opcache` (recommended)
    * `redis` (optional, for the [Redis cache](configuration.md#cache))
* [MariaDB](https://mariadb.org/) or [MySQL](https://www.mysql.com/)
* [OpenSSL](https://www.openssl.org/) command line tool (`openssl`) – used to
  create [MailCrypt](mailcrypt.md) keys
* [GnuPG](https://gnupg.org/) 2.1.14 or newer – used to validate uploaded
  [OpenPGP keys](wkd.md)
* A process supervisor such as systemd for the
  [background services](background-services.md)
* An SMTP server to send emails (welcome mails, notifications, reports)
* HTTPS with a valid certificate

!!! warning "Database support"

    Only MariaDB and MySQL are supported. The database migrations use
    MySQL-specific SQL, so PostgreSQL and SQLite can't be upgraded between
    releases.

Node.js and Composer are **not** needed: the release tarball contains all PHP
dependencies and the built frontend assets.

## Mail host

* [Dovecot](https://www.dovecot.org/) **2.4** with Lua support
  (`dovecot-auth-lua`) and `lua-json` – for authentication and
  [MailCrypt](mailcrypt.md)
* [Postfix](https://www.postfix.org/) with socketmap support and the
  [userli-postfix-adapter](https://github.com/systemli/userli-postfix-adapter)
  (requires Redis)

Userli, Dovecot and Postfix can run on the same host or on different hosts.
The mail host needs HTTPS access to Userli.

## Optional

* [Roundcube](https://roundcube.net/) with the
  [Userli plugin](integrations/roundcube.md)
* [Keycloak](https://www.keycloak.org/) with the
  [Userli user provider](integrations/keycloak.md) for single sign-on
* [Prometheus node exporter](https://github.com/prometheus/node_exporter) for
  [metrics](monitoring.md)
