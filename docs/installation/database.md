# Create database

Create Userli database and database user.
<!--more-->
For simplicity, the user has full access to `userli` database.

```shell
mysql -e 'CREATE DATABASE userli'
mysql -e 'CREATE USER `userli`@`localhost` IDENTIFIED BY "<password>"'
mysql -e 'GRANT ALL PRIVILEGES ON userli.* TO `userli`@`localhost`'
```
