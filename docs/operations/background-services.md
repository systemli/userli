# Background services

Besides the webserver, Userli needs two long-running processes:

| Process       | Command | Purpose |
|---------------|---------|---------|
| **Worker**    | `bin/console messenger:consume async` | Sends emails (welcome, recovery, notifications, reports) and webhooks, and runs the maintenance tasks. |
| **Scheduler** | `bin/console messenger:consume scheduler_maintenance` | Triggers the recurring [maintenance tasks](../admin/maintenance.md) on schedule. |

!!! warning

    Without the worker, no emails and no webhooks are sent and maintenance
    tasks never run. Without the scheduler, maintenance tasks only run when
    started manually in **Admin → Maintenance**.

Both processes should be restarted regularly to free memory and to pick up new
code after updates. The `--time-limit` option makes them exit after the given
number of seconds; the process supervisor starts them again.

## systemd

Create one unit per process. Adjust user, path and PHP binary.

```ini title="/etc/systemd/system/userli-worker.service"
[Unit]
Description=Userli worker
After=network.target mariadb.service

[Service]
User=userli
Group=userli
WorkingDirectory=/var/www/userli
ExecStart=/usr/bin/php bin/console messenger:consume async --time-limit=3600 --memory-limit=128M
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```ini title="/etc/systemd/system/userli-scheduler.service"
[Unit]
Description=Userli scheduler
After=network.target mariadb.service

[Service]
User=userli
Group=userli
WorkingDirectory=/var/www/userli
ExecStart=/usr/bin/php bin/console messenger:consume scheduler_maintenance --time-limit=3600 --memory-limit=128M
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Enable and start them:

```shell
sudo systemctl daemon-reload
sudo systemctl enable --now userli-worker userli-scheduler
```

## Queue

Queued messages are stored in the database by default. To use Redis or AMQP,
set `MESSENGER_TRANSPORT_DSN`, see [Configuration](configuration.md).

Failed messages are retried three times (after 10 seconds, 1 minute and 6
minutes). To inspect the queue:

```shell
bin/console messenger:stats
```

## Scheduler state

The scheduler remembers when tasks ran last. If it was not running, it catches
up missed tasks on start. The state is stored in the cache – with several
hosts or containers, use [Redis](configuration.md#cache) so that all share the
same state. Run only **one** scheduler process.
