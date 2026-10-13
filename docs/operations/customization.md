# Customization

## Names, URLs and addresses

Application name, project name and URLs, sender addresses and the webmail link
are [settings](../admin/settings.md) in the admin interface.

## Texts

All texts of the web interface and of the emails come from translation files.
The defaults are in `default_translations/<lang>/messages.<lang>.yml` and
`validators.<lang>.yml`. They are replaced on every update – don't edit them.

To change a text, override its key in
`translations/<lang>/messages.<lang>.yml` in the installation directory. You
only need to include the keys you want to change:

```yaml title="translations/en/messages.en.yml"
welcome:
  custom-bottom: >
    <h3>Need help?</h3>
    <p>Visit our <a href="https://www.example.org/help">help pages</a>.</p>
```

Clear the cache afterwards:

```shell
bin/console cache:clear
```

Look up the key of a text in
[`default_translations/en/messages.en.yml`](https://github.com/systemli/userli/blob/main/default_translations/en/messages.en.yml).
Override every language you offer – missing keys fall back to the default text
of that language, then to English.

Useful keys:

| Key | Where |
|-----|-------|
| `welcome.custom-bottom` | Additional block at the bottom of the welcome page after registration. Empty by default. HTML is allowed and sanitized. |
| `mail.welcome-body` | Welcome email. It mentions a quota and invite codes – adjust it to your setup. |

!!! note

    Keep your `translations/` directory when updating, see
    [Updating](updating.md).

## Languages

Userli ships with English, German, Spanish, French, Italian, Portuguese,
Bokmål and Swiss German. Translations are maintained on
[Weblate](https://hosted.weblate.org/engage/userli/) – improvements there
benefit everybody. See [Translations](../development/translations.md).
