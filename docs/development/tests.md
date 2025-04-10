# Tests


Create sample test data:

=== "podman"
     ```shell
     podman compose exec userli bin/console doctrine:fixtures:load --group=basic --env=test --no-interaction
     ```

=== "docker"
     ```shell
     docker compose exec userli bin/console doctrine:fixtures:load --group=basic --env=test --no-interaction
     ```

Run tests:

=== "podman"
     ```shell
     podman compose exec userli bin/phpunit
     ```

=== "docker"
    ```shell
    docker compose exec userli bin/phpunit
    ```
    
