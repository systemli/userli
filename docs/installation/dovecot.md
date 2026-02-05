# Set up Dovecot authentication

Userli provides an API for `userdb` and `passdb` lookups.

An adapter script written in Lua is provided to use for [Lua based authentication in Dovecot](https://doc.dovecot.org/latest/core/config/auth/databases/lua.html#lua-authentication-database-lua). The script is released as a separate tarball with each release and is only guaranteed to be compatible with the same version of Userli.

If the mailcrypt is enabled in Userli, the adapter script will also forward the required key material with each lookup.

## On the Userli Host

Create an API token with the scope `dovecot` and use it to authenticate requests from the Dovecot host.

- Via UI: Settings → API Tokens (`/settings/api`) → Create token → select scope `dovecot`.
- Via CLI:

    ```bash
    bin/console app:api-token:create --name "Dovecot" --scopes dovecot
    ```

!!! warning "Replaced configuration"

    The previous settings `DOVECOT_API_ENABLED`, `DOVECOT_API_IP_ALLOWLIST`, and `DOVECOT_API_ACCESS_TOKEN` have been removed.
    Use a scoped API token instead and send it via the HTTP header `Authorization: Bearer <PLAIN_TOKEN>`.

## On the Dovecot host

### Install Lua dependencies

On Debian based systems, run

```shell
sudo apt update
sudo apt install lua5.4 lua-json dovecot-auth-lua
```

### Install Userli-Dovecot-Adapter

Install the adapter script to a suitable location, like `/usr/local/bin/`

```shell
cd /usr/local/bin/
wget https://github.com/systemli/userli/releases/download/x.x.x/userli-dovecot-adapter-x.x.x.tar.gz
# Check signature and hash sum, if you know how to
tar -xvzf userli-dovecot-adapter-x.x.x.tar.gz
```

### Export environment variables

- `USERLI_API_ACCESS_TOKEN` (required): plain API token with the `dovecot` scope
- `USERLI_HOST` (required): host (and optional port) of the Userli instance, without a path
- `DOVECOT_LUA_AGENT`, defaults to "Userli-Dovecot-Adapter".
- `DOVECOT_LUA_INSECURE`, defaults to `false`. Connect to the Userli host via unencrypted HTTP.
- `DOVECOT_LUA_MAX_ATTEMPTS`, defaults to `3`.
- `DOVECOT_LUA_TIMEOUT`, defaults to `10000`.

### Example configuration

`/etc/dovecot/conf.d/auth-lua.conf.ext`:

```text
# Any of the above env vars needs to be explicitly imported here,
# in order to be available to the adapter script:
import_environment {
  USERLI_API_ACCESS_TOKEN=
  USERLI_HOST=
}

passdb lua {
  driver = lua
  lua_file = /usr/local/bin/userli-dovecot-adapter.lua
}

userdb lua {
  driver = lua
  lua_file = /usr/local/bin/userli-dovecot-adapter.lua
}
```

## MailCrypt

In order to enable MailCrypt in Dovecot, the following is required:

- Add `mail_crypt` to the `mail_plugins` list in `/etc/dovecot/conf.d/10-mail.conf`
- Set `crypt_write_algorithm = none` in `/etc/dovecot/conf.d/90-mail-crypt.conf` (Dovecot 2.4+)
- For Dovecot 2.3, use `mail_crypt_save_version = 0` instead

The latter disables MailCrypt per default and is necessary to not break incoming mail for legacy users without MailCrypt keys.
The adapter script automatically sets `crypt_write_algorithm = aes-256-gcm-sha256` (or `mail_crypt_save_version = 2` for Dovecot 2.3) for all users with MailCrypt keys.
