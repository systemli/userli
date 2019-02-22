+++
title = "Configuration"
description = ""
weight = 2
+++

Adjust the default values by creating a `.env` file.
<!--more-->

```
APP_ENV=prod
APP_NAME=Userli
APP_SECRET=<random secret string>
APP_URL=https://users.example.org/
DATABASE_DRIVER=pdo_mysql
DATABASE_URL=mysql://mailuser:<password>@127.0.0.1:3306/mail
MAILER_URL=smtp://localhost:25?encryption=&auth_mode=
PROJECT_NAME=example.org
PROJECT_URL=https://www.example.org/
DOMAIN=example.org
SENDER_ADDRESS=userli@example.org
NOTIFICATION_ADDRESS=admin@example.org
SEND_MAIL=true
LOCALE=en
HAS_SINA_BOX=false
MAIL_CRYPT_ENABLED=1
MAIL_CRYPT_AUTO=1
```
