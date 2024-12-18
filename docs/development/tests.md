# Tests

## Linting, unit tests and functional tests

```shell
vagrant up && vagrant ssh
make test
make integration
```

## Test checkpassword script

```shell
# Start vagrant box and login
vagrant up && vagrant ssh
# Create DB schema and load fixtures
bin/console doctrine:schema:create
bin/console doctrine:fixture:load
# Run `app:users:checkpassword` locally. First should return `0`, second `1`
echo -en 'user@example.org\0password' | ./bin/console app:users:checkpassword /bin/true; echo $?
echo -en 'user@example.org\0wrong' | ./bin/console app:users:checkpassword /bin/true; echo $?
# Logout from vagrant and test via IMAP login
exit
./tests/test_checkpassword_login.sh
```
