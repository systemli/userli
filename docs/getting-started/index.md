# Getting Started

The easiest way to get started with Userli is to use podman or docker.
We provide a `docker-compose.yml` file that starts Userli and MariaDB.
This is not recommended for production use, but it is a good way to get started.

!!! info
    If you don't have podman or docker installed, you can find the installation instructions on the [podman website](https://podman.io/getting-started/installation) or the [docker website](https://docs.docker.com/get-docker/).

1. Start Userli and MariaDB with podman or docker:
    
    Using podman: 

    ```shell
    podman compose up -d
    ```

    Using docker:

    ```shell
    docker compose up -d
    ```

2. Initialize the database:

    Using podman:

    ```shell
    podman compose exec userli bin/console doctrine:schema:create
    ```

    Using docker:

    ```shell
    docker compose exec userli bin/console doctrine:schema:create
    ```

3. Open your browser and go to [http://localhost:8000](http://localhost:8000)
