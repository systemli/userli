# Getting started

We provide a `docker-compose.yml` file that starts Userli with MariaDB, Dovecot, Roundcube and Mailcatcher to set up a development environment.
Userli will be available at [http://localhost:8000](http://localhost:8000) and Roundcube at [http://localhost:8001](http://localhost:8001).
Mails will be caught by Mailcatcher and can be viewed at [http://localhost:1080](http://localhost:1080).

## Requirements

- `docker-compose` or `podman-compose`
- `yarn` (or `yarnpkg` on Ubuntu or Debian based systems)
- `composer`
- `make`

!!! info
    If you don't have podman or docker installed, you can find the installation instructions on the [podman website](https://podman.io/getting-started/installation) or the [docker website](https://docs.docker.com/get-docker/).

## Set up

Start the containers:

=== "podman"

    ```shell
    podman compose up -d
    ```

=== "docker"

    ```shell
    docker compose up -d
    ```

!!! info
    This command will initiate building the containers on first run. Append `--build` to always force a full rebuild

Install PHP dependencies, run composer scripts and update JavaScript assets:

```shell
make assets
```

!!! tip
    See the contents of the `Makefile` if you are interested what each `make`-command does.

Initialize the database:

=== "podman"

    ```shell
    podman compose exec userli bin/console doctrine:schema:create
    ```

=== "docker"
    ```shell
    docker compose exec userli bin/console doctrine:schema:create
    ```

Install sample data:

=== "podman"

    ```shell
    podman compose exec userli bin/console doctrine:fixtures:load
    ```

=== "docker"

    ```shell
    docker compose exec userli bin/console doctrine:fixtures:load
    ```

!!! info
    The `doctrine:fixtures:load` command will create four new accounts with corresponding roles (`admin`, `user`, `support`, `suspicious`) as well as some random aliases and vouchers. The domain for all accounts is "example.org" and the password is "password".

    If you want to see more details about how the users are created, see `src/DataFixtures`.

Open your browser and go to [http://localhost:8000](http://localhost:8000)

## Troubleshooting

On systems with SELinux enabled, the webserver might throw an error due to broken filesystem permissions.
To fix this, create `docker-compose.override.yml` in the root directory with following content:

```yaml
---
services:
  userli:
    security_opt:
      - label=disable

  dovecot:
    security_opt:
      - label=disable
```
