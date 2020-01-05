+++
title = "Configuration"
description = ""
weight = 3
+++

You can personalize your Userli instance by creating `.env.local`,
which overrides some values from `.env`. You should at least configure
the following values.
<!--more-->

```
APP_ENV=prod
APP_SECRET=<random secret string>
APP_URL=https://users.example.org
DATABASE_URL=mysql://userli:<password>@127.0.0.1:3306/userli
MAILER_URL=smtp://localhost:25?encryption=&auth_mode=
PROJECT_NAME=example.org
PROJECT_URL=https://www.example.org
SENDER_ADDRESS=userli@example.org
NOTIFICATION_ADDRESS=admin@example.org
```

Look into `.env` to get more information about variables and how to handle them.
