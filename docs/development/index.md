# Development

## Development with Vagrant

### Requirements

- [Vagrant](https://vagrantup.com/)
- [VirtualBox](https://www.virtualbox.org/)

### Start Vagrant box

````shell
git submodule update --init
vagrant up && vagrant ssh

# create database and schema
bin/console doctrine:schema:create

# get node modules
yarn

# update assets
yarn encore dev
````

Visit you local instance at http://192.168.60.99/.

## Development on macOS

### Requirements

- [Homebrew](https://brew.sh/index_de)
- [Docker](https://www.docker.com/)

### Start the environment

```shell
# spin up mariadb
docker-compose up -d

brew install php@7.4
export PATH="/opt/homebrew/opt/php@7.4/bin:$PATH"

# install dependencies and run composer scripts
composer install --ignore-platform-reqs

# create database and schema
bin/console doctrine:schema:create

# get node modules
yarn

# update assets
yarn encore dev

# start the server
bin/console server:run
```

## Install sample data

```shell
bin/console doctrine:fixtures:load
```

The `doctrine:fixtures:load` command will create four new accounts with
corresponding roles (`admin`, `user`, `support`, `suspicious`) as well
as some random aliases and vouchers. The domain for all accounts is
"example.org" and the password is "password".

If you want to see more details about how the users are created, see
`src/DataFixtures`.

{{%children style="h2" description="true"%}}
