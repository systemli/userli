# Logs

## Userli

Userli utilizes the [Monolog](https://symfony.com/doc/6.4/logging.html#monolog) for logging which is configured in
`config/packages/monolog.yaml`.

Logs are written to `var/log/text.log` and `var/log/dev.log` when running in the `test` or `dev` environment respectively.

The logs are JSON formatted.

Inspecting the logs:

```shell
tail -f var/log/dev.log | jq
```

## Docker/Podman

Sometimes it's necessary to inspect the logs of the containers.

Say you want to inspect the logs for the `dovecot` container:

=== "podman"

    ```shell
    podman compose logs -f dovecot
    ```

=== "docker"

    ```shell
    docker compose logs -f dovecot
    ```

