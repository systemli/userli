# Console commands

Userli ships with console commands for tasks that are not available in the web
interface or that are useful for scripts. Run them from the installation
directory as the user that owns the files:

```shell
bin/console <command> --help
```

In containers, prefix them with `docker compose exec userli`.

Commands that change users support `--dry-run` to show what would happen.

## Users

| Command | Description |
|---------|-------------|
| `app:admin:password [password]` | Set the password of `postmaster@<default domain>`. Creates the account (and domain) if missing. |
| `app:users:delete --user <email>` | [Delete a user](../admin/users.md#delete-a-user) (soft delete). |
| `app:users:restore --user <email>` | Restore a deleted user. |
| `app:users:reset --user <email>` | Reset a user: new password, new MailCrypt keys, new recovery token, 2FA off. **Existing encrypted emails become unreadable.** |
| `app:users:quota --user <email>` | Show the mailbox quota of a user. |
| `app:users:mailcrypt --user <email> [password]` | Show the MailCrypt values of a user. |
| `app:users:registration:mail --user <email> [--locale de]` | Send the welcome email again. |

## Aliases and invite codes

| Command | Description |
|---------|-------------|
| `app:alias:delete --alias <email> [--user <email>]` | Delete an alias. |
| `app:voucher:create --user <email> [--count 3] [--domain <domain>] [--print] [--print-links]` | Create invite codes for a user and optionally print the codes or registration links. |
| `app:voucher:count --user <email>` | Show used and unused invite codes of a user. |

## Domains

| Command | Description |
|---------|-------------|
| `app:domain:delete --domain <domain>` | Delete a domain **and all its users, aliases and invite codes**. Try `--dry-run` first. |

## OpenPGP keys

| Command | Description |
|---------|-------------|
| `app:openpgp:import-key <email> <file>` | Import and publish a key for an address. |
| `app:openpgp:show-key <email>` | Show the published key of an address. |
| `app:openpgp:delete-key <email>` | Delete the published key of an address. |

## API tokens

| Command | Description |
|---------|-------------|
| `app:api-token:create --name <name> --scopes <scope> [--scopes <scope>]` | Create a token. Scopes: `dovecot`, `postfix`, `roundcube`, `keycloak`, `retention`. Prints the plain token once. |
| `app:api-token:delete --token <plain token>` | Delete a token. |

## Monitoring

| Command | Description |
|---------|-------------|
| `app:metrics` | Print metrics in Prometheus format, see [Monitoring](monitoring.md). |

## Useful Symfony commands

| Command | Description |
|---------|-------------|
| `doctrine:migrations:migrate` | Apply database migrations. |
| `doctrine:migrations:status` | Show the migration status. |
| `cache:clear` | Clear the cache, for example after changing `.env.local` or translations. |
| `messenger:consume async` | Run the [worker](background-services.md). |
| `messenger:consume scheduler_maintenance` | Run the [scheduler](background-services.md). |
| `messenger:stats` | Show the number of queued messages. |
| `messenger:failed:show` | Show failed messages. |
| `debug:scheduler` | Show the scheduled maintenance tasks. |
