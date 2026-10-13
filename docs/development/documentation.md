# Documentation

This documentation is built with [MkDocs](https://www.mkdocs.org/) and
[Material for MkDocs](https://squidfunk.github.io/mkdocs-material/). The
sources are in `docs/`, the navigation is defined in `mkdocs.yml`. Every push
to `main` publishes it to
[systemli.github.io/userli](https://systemli.github.io/userli/).

## Preview locally

With [mise](https://mise.jdx.dev/):

```shell
mise run docs
```

Without mise, with [uv](https://docs.astral.sh/uv/):

```shell
uvx --with-requirements docs/requirements.txt mkdocs serve --livereload --dev-addr 127.0.0.1:8100
```

Open [http://127.0.0.1:8100](http://127.0.0.1:8100). The page reloads
automatically when you change a file. Port 8100 is used because the
development environment already uses 8000 and 8001.

## Structure

The documentation is organized by audience:

| Section | Audience | Writing style |
|---------|----------|---------------|
| `user/` | Users of an email service | No technical knowledge required. Explain *what* and *why*, use UI labels exactly as shown in English, avoid jargon. |
| `admin/` | Admins and domain admins | Familiar with the web interface, not necessarily with servers. Explain effects and consequences of actions. |
| `operations/` | Server operators | Linux, webserver, database and mail server knowledge. Give complete, copy-pasteable configuration. |
| `development/` | Contributors | Know PHP/Symfony. Point to code and conventions. |

When documenting a feature, check whether every audience needs something:
how users use it, how admins configure it, how operators enable it.

## Guidelines

* Write in English, short sentences, active voice.
* Use admonitions (`!!! note`, `!!! warning`, `!!! danger`) for important
  information, especially for data loss.
* Use relative links to other pages (`../admin/settings.md#mta-sts`). MkDocs
  checks them on build.
* When moving a page, add a redirect in the `redirects` plugin section of
  `mkdocs.yml`, so that external links keep working.
* Show screenshots with `{class="screenshot"}`:

    ```markdown
    ![Users](../assets/images/admin-users.png){class="screenshot"}
    ```

* Screenshots are taken with the example data from `make fixtures` and stored
  in `docs/assets/images/`.
