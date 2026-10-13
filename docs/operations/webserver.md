# Webserver

Userli is a [Symfony application](https://symfony.com/doc/current/setup/web_server_configuration.html)
and runs behind any webserver that can talk to PHP-FPM via FastCGI.

* The document root is the `public/` directory of the installation, for
  example `/var/www/userli/public`.
* All requests that don't match a file have to be passed to `public/index.php`.
* Always use HTTPS.

The examples below are starting points. Adjust paths, PHP-FPM socket and TLS to
your system.

## Caddy

[Caddy](https://caddyserver.com/) obtains TLS certificates automatically.

```caddy
users.example.org {
    root * /var/www/userli/public
    encode gzip
    php_fastcgi unix//run/php/php8.4-fpm.sock
    file_server
}
```

## Nginx

```nginx
server {
    listen 443 ssl;
    http2 on;
    server_name users.example.org;

    ssl_certificate     /etc/letsencrypt/live/users.example.org/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/users.example.org/privkey.pem;

    root /var/www/userli/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    # Deny access to all other PHP files
    location ~ \.php$ {
        return 404;
    }
}

server {
    listen 80;
    server_name users.example.org;
    return 301 https://$host$request_uri;
}
```

## Apache

Requires `mod_rewrite`, `mod_ssl`, `mod_headers` and `mod_proxy_fcgi`.

```apache
<VirtualHost *:80>
    ServerName users.example.org
    Redirect permanent / https://users.example.org/
</VirtualHost>

<VirtualHost *:443>
    ServerName users.example.org
    DocumentRoot /var/www/userli/public

    <Directory /var/www/userli/public>
        AllowOverride None
        Require all granted
        FallbackResource /index.php
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.4-fpm.sock|fcgi://localhost"
    </FilesMatch>

    Protocols h2 http/1.1
    SSLEngine On
    SSLCertificateFile    /etc/letsencrypt/live/users.example.org/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/users.example.org/privkey.pem

    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</VirtualHost>
```

## Additional hostnames

* **Web Key Directory:** If the domains of your users don't point to Userli,
  forward `/.well-known/openpgpkey/` from those hosts to Userli. See
  [Web Key Directory](wkd.md#reverse-proxy-setup).
* **MTA-STS:** `mta-sts.<domain>` of every mail domain has to reach Userli. See
  [MTA-STS](mta-sts.md#reverse-proxy-setup).

## Security headers

Userli sets a Content Security Policy, `X-Frame-Options`,
`X-Content-Type-Options` and `Referrer-Policy` itself. You only need to add
`Strict-Transport-Security` in the webserver.

If Userli runs behind a reverse proxy, configure
[trusted proxies](security.md#reverse-proxies).
