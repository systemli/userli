# Configuration

You can personalize your Userli instance by creating `.env.local`,
which overrides some values from `.env`. You should at least configure
the following values.
<!--more-->

```text
APP_ENV=prod
APP_SECRET=<random secret string>
DATABASE_URL=mysql://userli:<password>@127.0.0.1:3306/userli
MAILER_DSN=smtp://localhost:25
```

Look into `.env` to get more information about variables and how to handle them.

## Cache

By default, Userli uses the filesystem for caching. This works well for small installations.

For larger installations, Redis is recommended as a shared cache backend. 
To enable Redis, set the `REDIS_URL` environment variable in your `.env.local`:

```text
REDIS_URL=redis://localhost:6379
```

When `REDIS_URL` is set, Userli automatically switches from filesystem to
Redis for all caching (application cache, Doctrine result cache, scheduler
state, etc.).

!!! note

    The PHP Redis extension (`phpredis`) must be installed for the Redis
    adapter to work. It is included in the official Docker image.

!!! warning

    If you run multiple containers (web, worker, scheduler) without Redis,
    each container maintains its own isolated filesystem cache. This means
    cache invalidation in one container does not affect the others.

