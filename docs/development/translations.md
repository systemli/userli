# Translations

Userli is available in English, German, Spanish, French, Italian, Portuguese,
Norwegian Bokmål and Swiss German.

## Translate on Weblate

All languages except English and German are translated on
[Weblate](https://hosted.weblate.org/engage/userli/). You don't need to know
Git or PHP – create a Weblate account and start translating. The maintainers
merge the translations into the repository.

[![Translation status](https://hosted.weblate.org/widgets/userli/-/multi-auto.svg)](https://hosted.weblate.org/engage/userli/)

To add a new language, start it on Weblate and open an issue so that it can be
added to the language switcher.

## English and German

English and German are maintained directly in the repository:

```text
default_translations/
├── en/
│   ├── messages.en.yml     UI and email texts
│   └── validators.en.yml   Validation messages
└── de/
    ├── messages.de.yml
    └── validators.de.yml
```

When you add or change a user-facing text:

1. Add the key to `messages.en.yml` **and** `messages.de.yml`.
2. Use the key in templates with the `|trans` filter:

    ```twig
    {{ 'welcome.invitations.headline'|trans }}
    {{ 'welcome.invitations.text'|trans({'%limit%': limit, '%waitingPeriodDays%': days}) }}
    ```

3. In PHP, use the `TranslatorInterface` or pass translation keys to flash
   messages and constraints.

Never hard-code user-facing text in templates or code. Weblate picks up new
English keys for the other languages automatically.

## Overriding texts in an installation

Operators can override texts without changing the code, see
[Customization](../operations/customization.md).
