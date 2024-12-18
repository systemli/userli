# Webserver configuration

Userli has to be installed like any other [Symfony application](https://symfony.com/doc/current/setup/web_server_configuration.html).

Below, you'll find some example configurations for webservers.
Don't blindly copy them, but adjust them to your needs.

## Caddy

```text
:8080
gzip
root /var/www/userli/public
# PHP-FPM Configuration
fastcgi / /run/php/php8.0-fpm.sock php
rewrite {
  to {path} /index.php?{query}
}
```

## Nginx

```text
server {
    listen  80;

    root /vagrant/public;
    index index.php;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    error_page 404 /404.html;

    error_page 500 502 503 504 /50x.html;
        location = /50x.html {
        root /usr/share/nginx/www;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTPS off;
    }
}
```

## Apache2

```text
<VirtualHost *:80>

    ServerName users.example.org
    ServerAdmin admin@example.org
    RewriteEngine On
    RewriteRule .* https://%{SERVER_NAME}%{REQUEST_URI} [NE,R,L]

</VirtualHost>

<VirtualHost *:443>

    ServerName users.example.org
    ServerAdmin admin@example.org

    DocumentRoot /var/www/users.example.org/www/public

    <Directory /var/www/users.example.org/www/public>
        AllowOverride AuthConfig FileInfo Indexes Limit Options=ExecCGI,Includes,Indexes,SymLinksIfOwnerMatch,MultiViews
        Options -Indexes -MultiViews +SymLinksIfOwnerMatch

        LimitRequestBody 10485760

    </Directory>

    <Directory /var/www/users.example.org/www/public/.well-known>
        Require all granted
    </Directory>

    SetEnv APP_ENV prod

    <IfModule suexec_module>
        SuexecUserGroup userli userli
    </IfModule>

    <IfModule fcgid_module>
        AddHandler fcgid-script .php
        FCGIWrapper /var/www/users.example.org/php-fcgi/php-fcgi-starter .php

        IPCConnectTimeout 20
        IPCCommTimeout 60
        FcgidBusyTimeout 60
        MaxRequestLen 10485760

        <Directory /var/www/users.example.org/www/public>
            Options +ExecCGI
        </Directory>
    </IfModule>

    <IfModule mod_headers.c>
        Header add X-Content-Type-Options "nosniff"
        Header add X-XSS-Protection "1; mode=block"
        Header set Referrer-Policy "no-referrer"
        Header add X-Frame-Options "SAMEORIGIN"
    </IfModule>
    ErrorLog  "|/usr/bin/logger -t apache -p local0.error"

    Protocols h2 http/1.1

    SSLEngine On
    SSLCertificateFile /etc/letsencrypt/live/users.example.org/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/users.example.org/privkey.pem

    <IfModule mod_headers.c>
        Header always set Strict-Transport-Security: "max-age=31536000;includeSubdomains"
    </IfModule>

</VirtualHost>
```
