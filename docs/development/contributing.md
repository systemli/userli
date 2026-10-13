# Contributing

Userli is free software and every contribution is welcome – code, bug reports,
[translations](translations.md), [documentation](documentation.md) or ideas.

## Code of Conduct

This project follows the [Contributor Covenant](https://www.contributor-covenant.org/version/1/4/code-of-conduct/) Code of Conduct.
Please read the [full text](code_of_conduct.md) before participating.
For reports of abusive or unacceptable behavior, contact [userli@systemli.org](mailto:userli@systemli.org).

## Find something to work on

* Browse the [issue tracker](https://github.com/systemli/userli/issues),
  especially [good first issues](https://github.com/systemli/userli/labels/good%20first%20issue).
* [Report a bug or suggest a feature](https://github.com/systemli/userli/issues/new).
* For bigger changes, open an issue first to discuss the approach.
* Security issues: please report them privately, see
  [`SECURITY.md`](https://github.com/systemli/userli/blob/main/SECURITY.md).

## Workflow

1. Fork the repository and create a branch.
2. Set up the [development environment](index.md).
3. Make your change. Follow the [architecture conventions](architecture.md#conventions).
4. Add or update tests, see [Testing](testing.md).
5. Update the documentation for every audience that is affected – users,
   admins and operators, see [Documentation](documentation.md).
6. If operators have to do something when updating (new or removed environment
   variables, migrations with manual steps, changed Dovecot configuration),
   describe it in `UPGRADE.md`.
7. Run the code quality checks (below) and open a pull request.

CI runs PHPUnit, Behat, Vitest, PHP CS Fixer, Rector, Psalm, migration checks
and CodeQL on every pull request.

## Coding style

Code style is enforced by [PHP CS Fixer](https://cs.symfony.com/), [Rector](https://getrector.com/), and [Psalm](https://psalm.dev/).

=== "make"

    ```shell
    make lint    # check code style and Rector rules
    make fix     # apply PHP CS Fixer and Rector fixes
    make psalm   # static analysis
    ```

=== "composer"

    ```shell
    composer cs-check       # check code style
    composer cs-fix         # fix code style
    composer rector-check   # Rector dry-run
    composer rector-fix     # apply Rector changes
    composer psalm          # static analysis
    ```

For TypeScript:

```shell
yarn typecheck
```

!!! tip
    Run all checks before pushing to catch issues early.
    CI will reject code that fails these checks.

## Commits

- Use [Gitmojis](https://gitmoji.dev/) in commit messages, e.g.
  `✨ Add alias notes` or `🐛 Fix voucher count`
- Write commit messages in English

## Database changes

Change entities in `src/Entity/` and generate a migration:

```shell
bin/console doctrine:migrations:diff
```

Review the generated SQL. Migrations must work on MariaDB and MySQL.

## Translations

User-facing text always uses translation keys. Add new keys to the English
**and** German files, see [Translations](translations.md).
