# Getting started

The easiest way to get started with Userli is to use podman or docker.
We provide a `docker-compose.yml` file that starts Userli, Dovecot and MariaDB.

!!! info
    If you don't have podman or docker installed, you can find the installation instructions on the [podman website](https://podman.io/getting-started/installation) or the [docker website](https://docs.docker.com/get-docker/).


Start Userli, Dovecot and MariaDB with podman or docker:

=== "podman"

    ```shell
    podman compose up -d
    ```

=== "docker"

    ```shell
    docker compose up -d
    ```

Install dependencies and run composer scripts:

```shell
composer install --ignore-platform-reqs
```

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

Open your browser and go to [http://localhost:8000](http://localhost:8000)


!!! info
    The `doctrine:fixtures:load` command will create four new accounts with corresponding roles (`admin`, `user`, `support`, `suspicious`) as well as some random aliases and vouchers. The domain for all accounts is "example.org" and the password is "password".
    
    If you want to see more details about how the users are created, see `src/DataFixtures`.
