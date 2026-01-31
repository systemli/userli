# Tests

You will need to run the database migrations once:

```shell
bin/console doctrine:migrations:migrate --no-interaction --env=test
```

## PHPUnit

Create sample test data and run tests:

```shell
make test
```

## Behat

Run Behat
```shell
make integration
```

!!! tip
    See the contents of the `Makefile` if you are interested what each `make`-command does.

## Testing the Dovecot integration

After spinning up the docker/podman containers, find out the address of internal subnet create of the containers.

=== "podman"

    ```shell
    podman network inspect userli_userli | grep subnet
    ```

=== "docker"

    ```shell
    docker network inspect userli_userli | grep subnet
    ```

The output should look like this:

```
"subnet": "10.89.0.0/24"
```

Enable the Dovecot API in Userli by adding these environment variables to your `.env.local`, using the subnet that you identified in the last step. Make sure to use the same Access Token as is used in `docker-compose.yml`

```
DOVECOT_API_ENABLED=true
DOVECOT_API_ACCESS_TOKEN="dovecot"
DOVECOT_API_IP_ALLOWLIST="<your docker/podman network>"

```

After enabling the Dovecot API, you might need to restart the dovecot container.

Now you should be able to test the Dovecot API from withing the Dovecot container:

=== "podman"

    ```shell
    podman compose exec dovecot doveadm auth test user@example.org password
    ```

=== "docker"

    ```shell
    podman compose exec dovecot doveadm auth test user@example.org password
    ```

See the Dovecot [documentation](https://doc.dovecot.org/2.3/admin_manual/debugging/debugging_authentication/) for more context.

