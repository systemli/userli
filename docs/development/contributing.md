# Contributing

## Code of Conduct

This project follows the [Contributor Covenant](https://www.contributor-covenant.org/version/1/4/code-of-conduct/) Code of Conduct.
Please read the [full text](code_of_conduct.md) before participating.
For reports of abusive or unacceptable behavior, contact [userli@systemli.org](mailto:userli@systemli.org).

## Coding style

Code style is enforced by [PHP CS Fixer](https://cs.symfony.com/), [Rector](https://getrector.com/), and [Psalm](https://psalm.dev/).

Check code style without making changes:

```shell
composer cs-check
```

Automatically fix code style:

```shell
composer cs-fix
```

Run Rector refactoring checks:

```shell
composer rector-check   # dry-run
composer rector-fix     # apply changes
```

Run Psalm static analysis:

```shell
composer psalm
```

!!! tip
    Run all three checks before pushing to catch issues early.
    CI will reject code that fails these checks.

## Commits

- Use [Gitmojis](https://gitmoji.dev/) in commit messages
- Write commit messages in English

## Translations

Translation files are in `default_translations/`.
English and German translations are managed manually in the repository.
All other languages are managed via Weblate.

All user-facing text in templates must use the `|trans` Twig filter with translation keys from `default_translations/`.
