# Roundcube

The [Roundcube Userli plugin](https://github.com/systemli/roundcube-userli-plugin)
creates a Roundcube identity for each alias of a user when they sign in to
[Roundcube](https://roundcube.net/). Users can then send emails from their
aliases without setting up identities manually.

The plugin also handles the sign-in: users with two-factor authentication enter
their code or use their security key in a second step. Roundcube then uses a webmail credential from
Userli for IMAP and SMTP instead of the password. The credential is valid for
12 hours and is revoked on logout. This is required for webmail if
[app passwords are required with 2FA](../../admin/settings.md#two-factor-authentication).

## On the Userli host

Create an API token with the scope `roundcube`:

```shell
bin/console app:api-token:create --name "Roundcube" --scopes roundcube
```

## On the Roundcube host

Install the plugin, either with Composer
([`systemli/userli`](https://packagist.org/packages/systemli/userli)) or by
cloning the repository into the `plugins/` directory, and enable it in the
Roundcube configuration:

```php title="config/config.inc.php"
$config['plugins'] = ['userli' /* , ... */];
```

Configure it in `plugins/userli/config.inc.php` (copy from
`config.inc.php.dist`):

```php title="plugins/userli/config.inc.php"
$config['userli_url'] = 'https://users.example.org';
$config['userli_token'] = '<PLAIN_TOKEN>';
$config['userli_ssl_verify'] = true;
```

## Security keys

With version 1.1 of the plugin or later, users can sign in with a security key.
Security keys are bound to a domain, so Userli and Roundcube need a common
parent domain, for example `users.example.org` and `mail.example.org`. In
Userli, set **Security Key Domain** to `example.org` and add the Roundcube
origin, e.g. `https://mail.example.org`, to **Additional Security Key Origins**
(see [Settings](../../admin/settings.md#two-factor-authentication)).

Keys that users added before the domain was set only work on the Userli host.
Users have to add them again. Without a security key that works there, users
sign in to webmail with a TOTP or backup code.

## Webmail link

Set the URL of Roundcube as **Webmail URL** in
[Admin → Settings](../../admin/settings.md#application-settings) to show a
*Webmail* card to users.
