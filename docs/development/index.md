# Getting started

We provide a `docker-compose.yml` file that starts Userli, Dovecot and MariaDB to set up a development environment.

## Requirements

- `docker-compose` or `podman-compose`
- `yarn` (or `yarn-pkg` on Ubuntu or Debian based systems)
- `composer`

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
    `$command compose up -d` will initiate building the containers on first run. Append `--build` to force a full rebuild



Install PHP dependencies, run composer scripts and update assets:


```shell
composer install --ignore-platform-reqs
yarn
yarn encore dev
```

Initialize the database and install sample data:

=== "podman"

    ```shell
    podman compose exec userli bin/console doctrine:schema:create
    podman compose exec userli bin/console doctrine:fixtures:load
    ```

=== "docker"
    ```shell
    docker compose exec userli bin/console doctrine:schema:create
    docker compose exec userli bin/console doctrine:fixtures:load
    ```


Open your browser and go to [http://localhost:8000](http://localhost:8000)


!!! info
    The `doctrine:fixtures:load` command will create four new accounts with corresponding roles (`admin`, `user`, `support`, `suspicious`) as well as some random aliases and vouchers. The domain for all accounts is "example.org" and the password is "password".
    
    If you want to see more details about how the users are created, see `src/DataFixtures`.
