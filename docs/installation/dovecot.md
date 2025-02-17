# Dovecot Authentication 

Userli provides an API for `userdb` and `passdb` lookups.
This can be used to authenticate users with Dovecot by using [Lua based authentication](https://doc.dovecot.org/2.3/configuration_manual/authentication/lua_based_authentication/).
An adapter script is provided in the source code in `contrib/dovecot/userli.lua`.

If the mailcrypt is enabled in Userli, the Lua adapter will also forward the required key material with each lookup.

## On the Userli Host

In `.env.local`, following environment variables needs to be configured:

```shell
DOVECOT_API_ENABLED=true
DOVECOT_API_IP_ALLOWLIST="127.0.0.1, ::1"
DOVECOT_API_ACCESS_TOKEN="replace-me-with-a-secure-token"
```

## On the Dovecot host

### Prerequisites

* `lua`
* `json-lua`
* `dovecot-auth-lua`

### Example configuration

`/etc/dovecot/conf.d/auth-lua.conf.ext`:

```text
import_environment=USERLI_API_ACCESS_TOKEN USERLI_HOST DOVECOT_LUA_DEBUG DOVECOT_LUA_INSECURE

passdb {
  driver = lua
  args = file=/path/to/userli-adapter.lua blocking=yes
}

userdb {
  driver = lua
  args = file=/path/to/userli-adapter.lua blocking=yes
}
```

Export the env vars to be picked up by Dovecots `import_environment` config:

- `USERLI_API_ACCESS_TOKEN` (mandatory)
- `USERLI_HOST` (mandatory)
- `DOVECOT_LUA_AGENT`, defaults "Dovecot-Lua-Auth"
- `DOVECOT_LUA_INSECURE`, to connect to the Userli host via unencrypted HTTP, defaults to `false`
- `DOVECOT_LUA_DEBUG`, defaults to `false`
- `DOVECOT_LUA_MAX_ATTEMPTS`, defaults to `3` 
- `DOVECOT_LUA_TIMEOUT`, defaults to `10000`

## MailCrypt

In order to enable MailCrypt in Dovecot, the following is required:

* Add `mail_crypt` to the `mail_plugins` list in `/etc/dovecot/conf.d/10-mail.conf`
* Set `mail_crypt_save_version = 0` in `/etc/dovecot/conf.d/90-mail-crypt.conf`

The latter disables MailCrypt per default and is necessary to not break incoming mail for legacy users without MailCrypt keys.
The Lua adapter automatically sets `mail_crypt_save_version = 2` for all users with MailCrypt keys.
