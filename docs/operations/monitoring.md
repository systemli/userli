# Monitoring and logs

## Metrics

`bin/console app:metrics` prints metrics in the
[Prometheus text format](https://prometheus.io/docs/instrumenting/exposition_formats/):

| Metric | Description |
|--------|-------------|
| `userli_users_total` | All users, including deleted |
| `userli_users_active_total` | Users that are not deleted |
| `userli_users_deleted_total` | Deleted users |
| `userli_users_weekly_active_total` | Users with login in the last 7 days |
| `userli_users_monthly_active_total` | Users with login in the last 30 days |
| `userli_users_recovery_token_total` | Users with a recovery token |
| `userli_users_mailcrypt_total` | Users with MailCrypt enabled |
| `userli_users_twofactor_total` | Users with 2FA enabled |
| `userli_users_mail_app_password_total` | Users with at least one mail app password |
| `userli_mail_app_passwords_total` | Mail app passwords |
| `userli_vouchers_total{type="redeemed"\|"unredeemed"}` | Invite codes |
| `userli_domains_total` | Domains |
| `userli_aliases_total` | Aliases |
| `userli_openpgpkeys_total` | Published OpenPGP keys |

There is no HTTP endpoint. Use the textfile collector of the
[node exporter](https://github.com/prometheus/node_exporter#textfile-collector),
for example with a cron job (`sponge` is part of `moreutils`):

```text title="/etc/cron.d/userli-metrics"
* * * * * userli cd /var/www/userli && bin/console app:metrics | sponge /var/lib/prometheus/node-exporter/userli.prom
```

## What to monitor

* **Webserver and PHP-FPM** – availability of the start page via HTTPS.
* **Worker and scheduler** – the processes are running. Check that the queue
  doesn't grow: `bin/console messenger:stats`.
* **Failed messages** – `bin/console messenger:failed:show`.
* **Failed webhook deliveries** – in **Admin → Webhooks → Deliveries**.
* **Integrations** – login failures in Dovecot and Postfix logs, metrics of the
  [Postfix adapter](integrations/postfix.md).
* **Certificates** – including `mta-sts.<domain>` and `openpgpkey.<domain>`.

## Logs

| Log | Where |
|-----|-------|
| Application errors | **syslog**, ident `userli`, JSON format. Only errors are written – with the 50 log entries before each error for context. 404 and 405 errors are ignored. |
| Deprecations | stderr of PHP-FPM, JSON format |
| Worker and scheduler | stdout/stderr of the processes, e.g. `journalctl -u userli-worker` |
| Requests | webserver access log |

```shell
journalctl -t userli
```

In containers without a syslog daemon, application errors are not visible.
Mount `/dev/log` from the host into the container, or check the worker output.

The [weekly report](../admin/maintenance.md) emails new registrations to the
notification address and is useful to notice spam waves.
