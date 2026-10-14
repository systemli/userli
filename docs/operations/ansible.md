# Deploy with Ansible

We maintain the Ansible role
[`systemli.userli`](https://github.com/systemli/ansible-role-userli) and use it
to run Userli at systemli.org. It automates the steps from
[Install from release](installation.md) and [Updating](updating.md).

The role targets Debian with a local MariaDB/MySQL server. Each role release
pins a Userli version, so updating the role updates Userli.

## What the role does

* Downloads the release tarball, checks its SHA-512 checksum and unpacks it
  into `/var/www/userli-x.y.z`
* Links `/var/www/userli` to the current release
* Writes `.env.local` from `userli_env_vars`
* Creates the database and the database user, and runs the migrations on the
  first run
* Installs and starts the `userli-worker` and `userli-scheduler`
  [systemd services](background-services.md)
* Deploys updates when you ask for it (see [Updating](#updating))
* Optionally installs a systemd timer that writes Prometheus metrics for the
  node exporter textfile collector (see [Monitoring](monitoring.md)); it
  needs `sponge` from the `moreutils` package

The role does **not** install PHP, the webserver or the database server, and
it does not write a webserver configuration. Set them up yourself (see
[Requirements](requirements.md) and [Webserver](webserver.md)) or with other
roles.

## Install the role

The role is published on
[Ansible Galaxy](https://galaxy.ansible.com/ui/standalone/roles/systemli/userli/).
Add it and the collections it uses to your `requirements.yml`:

```yaml title="requirements.yml"
roles:
  - name: systemli.userli

collections:
  - community.general
  - ansible.mysql
```

```shell
ansible-galaxy install -r requirements.yml
```

## Example playbook

```yaml title="userli.yml"
- name: Deploy Userli
  hosts: userli
  become: true
  roles:
    - systemli.userli
  vars:
    userli_mysql_password: "{{ vault_userli_mysql_password }}"
    userli_env_vars:
      - name: APP_ENV
        value: "prod"
      - name: APP_DEBUG
        value: "false"
      - name: APP_SECRET
        value: "{{ vault_userli_app_secret }}"
      - name: DATABASE_URL
        value: "{{ userli_db_url }}"
      - name: MAILER_DSN
        value: "smtp://localhost:25"
      - name: DEFAULT_URI
        value: "https://users.example.org"
```

!!! warning

    `userli_env_vars` replaces the default list. Always set `APP_SECRET` and
    `DATABASE_URL`, and keep secrets in Ansible Vault. See
    [Configuration](configuration.md) for all variables.

The first run with `-e userli_update=true` installs Userli and warms up the
cache:

```shell
ansible-playbook userli.yml -e userli_update=true
```

Then run the [initial setup](initial-setup.md).

## Important variables

| Variable | Default | Description |
|----------|---------|-------------|
| `userli_version` | latest release | Userli version to install |
| `userli_mysql_password` | – | Password of the database user (required) |
| `userli_mysql_user` / `userli_mysql_db` | `userli` | Database user and name |
| `userli_env_vars` | minimal set | Content of `.env.local` |
| `userli_home` | `/var/www` | Directory for the releases |
| `userli_user` / `userli_group` | `www-data` | System user that owns the application |
| `userli_webserver` | `apache2` | Webserver service stopped during updates |
| `userli_update` | `false` | Deploy a new version (cache, migrations, restarts) |
| `userli_primary` | `true` | Manage the database on this host |
| `userli_prometheus_textfile_collector_enabled` | `false` | Install the metrics timer |

See
[`defaults/main.yml`](https://github.com/systemli/ansible-role-userli/blob/main/defaults/main.yml)
for all variables.

To [customize](customization.md) texts or templates, put the files into a
`userli/` directory next to your playbook (for example
`userli/translations/en/messages.en.yml`). The role copies them into the
release directory.

## Updating

Read the [release notes](updating.md) first, back up the database and update
the role (or set `userli_version`). Then deploy with:

```shell
ansible-playbook userli.yml -e userli_update=true
```

With `userli_update=true` the role stops the webserver and the background
services, clears and warms up the cache, runs the migrations and starts
everything again.

!!! danger

    Without `userli_update=true`, a new `userli_version` is still unpacked and
    `/var/www/userli` is switched to it, but the cache and the migrations are
    not updated. Always pass `-e userli_update=true` when the version changes.
