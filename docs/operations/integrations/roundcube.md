# Roundcube

The [Roundcube Userli plugin](https://github.com/systemli/roundcube-userli-plugin)
creates a Roundcube identity for each alias of a user when they sign in to
[Roundcube](https://roundcube.net/). Users can then send emails from their
aliases without setting up identities manually.

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

## Webmail link

Set the URL of Roundcube as **Webmail URL** in
[Admin → Settings](../../admin/settings.md#application-settings) to show a
*Webmail* card to users.
