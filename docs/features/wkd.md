# Web Key Directory

Userli brings support for [OpenPGP Web Key
Directory](https://gnupg.org/faq/wkd.html), a OpenPGP key discovery system.
Users can import and update their OpenPGP key and it will be published in the
Web Key Directory according to the [OpenPGP Web Key Directory Internet
Draft](https://datatracker.ietf.org/doc/draft-koch-openpgp-webkey-service).

Importing OpenPGP keys requires [GnuPG](https://gnupg.org/) (version 2.1.14
or newer) to be installed, as the
[`pear/crypt_gpg`](https://pear.php.net/manual/en/package.encryption.crypt-gpg.intro.php)
library uses it to parse and validate uploaded keys.

Userli serves WKD keys directly via HTTP using the **Advanced** method. The
following routes are provided:

- `/.well-known/openpgpkey/{domain}/hu/{hash}` — returns the binary OpenPGP
  key for the given WKD hash
- `/.well-known/openpgpkey/{domain}/policy` — returns an empty policy file

Key lookups are cached in Redis with a 24-hour TTL and automatically
invalidated when keys are created, updated, or deleted.

## Reverse Proxy Setup

If Userli runs behind a reverse proxy, ensure that requests to
`/.well-known/openpgpkey/` are forwarded to the application.

### Nginx

```nginx
location /.well-known/openpgpkey/ {
    proxy_pass http://upstream;
}
```

### Apache 2

```apache
ProxyPass "/.well-known/openpgpkey/" "http://localhost:8000/.well-known/openpgpkey/"
ProxyPassReverse "/.well-known/openpgpkey/" "http://localhost:8000/.well-known/openpgpkey/"
```

### Caddy

```caddy
handle /.well-known/openpgpkey/* {
    reverse_proxy localhost:8000
}
```
