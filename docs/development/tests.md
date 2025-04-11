# Tests

You will need to create the database schema once:

```shell
bin/console doctrine:schema:create --env=test
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

