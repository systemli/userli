# Install from release

Release tarballs are the recommended way to install Userli on a server. They
contain the application, all PHP dependencies and the built frontend assets.

The steps below assume Debian/Ubuntu, a system user `userli` and the
installation directory `/var/www/userli`. Adjust them to your system.

!!! tip

    To automate these steps, use our [Ansible role](ansible.md).

## 1. Download

Get the [latest release](https://github.com/systemli/userli/releases/latest).
Each release provides `userli-x.y.z.tar.gz`, SHA-256/SHA-512 checksums and a
GPG signature (`.asc`).

```shell
cd /tmp
wget https://github.com/systemli/userli/releases/download/x.y.z/userli-x.y.z.tar.gz
wget https://github.com/systemli/userli/releases/download/x.y.z/userli-x.y.z.tar.gz.sha256
wget https://github.com/systemli/userli/releases/download/x.y.z/userli-x.y.z.tar.gz.sha256.asc

# Verify the hash of the release tarball against the hashsum file
sha256sum -c userli-x.y.z.tar.gz.sha256

# Verify the signature
gpg --verify userli-x.y.z.tar.gz.asc userli-x.y.z.tar.gz
```

!!! tip

    Check the exact file names on the release page. To verify the signature,
    import the release signing key first.

Unpack it:

```shell
sudo mkdir -p /var/www/userli
sudo tar -xzf userli-x.y.z.tar.gz -C /var/www/userli --strip-components=1
sudo chown -R userli:userli /var/www/userli
```

## 2. Create the database

Create a database and a database user:

```shell
sudo mysql -e 'CREATE DATABASE userli CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
sudo mysql -e "CREATE USER 'userli'@'localhost' IDENTIFIED BY '<password>'"
sudo mysql -e "GRANT ALL PRIVILEGES ON userli.* TO 'userli'@'localhost'"
```

## 3. Configure

Create `/var/www/userli/.env.local` with at least these values. See
[Configuration](configuration.md) for all options.

```shell
APP_ENV=prod
APP_SECRET=<random string, e.g. from: openssl rand -hex 32>
DATABASE_URL="mysql://userli:<password>@127.0.0.1:3306/userli?serverVersion=mariadb-10.11.0"
MAILER_DSN=smtp://localhost:25
DEFAULT_URI=https://users.example.org
```

## 4. Create the schema

```shell
cd /var/www/userli
sudo -u userli bin/console doctrine:migrations:migrate --no-interaction
sudo -u userli bin/console cache:warmup
```

## 5. Next steps

1. Configure the [webserver](webserver.md) with the document root
   `/var/www/userli/public`.
2. Start the [background services](background-services.md).
3. Run the [initial setup](initial-setup.md).
