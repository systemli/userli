# Installation

## Requirements

* Webserver (e.g [Caddy](https://caddyserver.com/))
* [PHP >= 8.4](https://secure.php.net/) with libsodium
* [MariaDB](https://mariadb.org/) or [MySQL](https://mysql.com/)
* [OpenSSL](https://www.openssl.org/) binary (for MailCrypt feature)
* [GnuPG](https://gnupg.org/) version 2.1.14 or newer (required by
  [`pear/crypt_gpg`](https://pear.php.net/manual/en/package.encryption.crypt-gpg.intro.php)
  for OpenPGP key import)

You can also run this application with PostgreSQL oder SQLite.
