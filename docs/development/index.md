# Development

## Development with Vagrant

### Requirements

- [Vagrant](https://vagrantup.com/) to set up a virtual machine as development environment.
- [VirtualBox](https://www.virtualbox.org/) as a [virtualisation provider](https://developer.hashicorp.com/vagrant/docs/providers/virtualbox) for Vagrant.
- [Ansible](https://www.ansible.com/) for provisioning the virtual machine.

!!! note

    The provisioning of the development environment is defined in `.Vagrantfile` and `.ansible/playbook.yml`.
    If you're unfamiliar with Vagrant, you might want to check out its [Quick Start guide](https://developer.hashicorp.com/vagrant/tutorials/getting-started).

### Start Vagrant box

````shell
# pull ansible roles for provisioning:
git submodule update --init

# start vagrant box.
# Implies "vagrant up --provision" when run for first time
vagrant up

## ssh into the virtual environment
vagrant ssh

# create database and schema
bin/console doctrine:schema:create

# get node modules
yarn

# update assets
yarn encore dev
````

Visit you local instance at [http://192.168.60.99/](http://192.168.60.99).

## Development on macOS

### Requirements

- [Homebrew](https://brew.sh/index_de)
- [Docker](https://www.docker.com/)

### Start the environment

```shell
# spin up mariadb
docker-compose up -d

brew install php@8.0
export PATH="/opt/homebrew/opt/php@8.0/bin:$PATH"

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
