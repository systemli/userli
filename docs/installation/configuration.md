# Configuration

You can personalize your Userli instance by creating `.env.local`,
which overrides some values from `.env`. You should at least configure
the following values.
<!--more-->

```
APP_ENV=prod
APP_SECRET=<random secret string>
DATABASE_URL=mysql://userli:<password>@127.0.0.1:3306/userli
MAILER_DSN=smtp://localhost:25
```

Look into `.env` to get more information about variables and how to handle them.
