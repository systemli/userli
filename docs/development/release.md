# Release

Release tarballs are the preferred way to distribute Userli.
This page explains how to create a release.

## Prerequisites

1. Update `CHANGELOG.md` with a section for the new version.
   The date **must match today's date** in `YYYY.MM.DD` format:

    ```markdown
    ## 6.3.0 (2026.03.01)

    ### Features and Improvements
    - ...
    ```

2. Commit and merge the changelog update before running the release script.

3. You need a [GitHub API token](https://github.com/settings/tokens) with the following scopes:

    ```text
    public_repo, repo:status, repo_deployment
    ```

4. You need a GPG key for signing the tag and tarballs.

## Create the release

Run the release script:

```shell
GITHUB_API_TOKEN=<token> GPG_SIGN_KEY="<key_id>" ./bin/github-release.sh <version>
```

The script will:

- Validate that `CHANGELOG.md` contains the version with today's date
- Create a GPG-signed git tag
- Build two release tarballs via `make release`:
    - `userli-<version>.tar.gz` -- the main application
    - `userli-dovecot-adapter-<version>.tar.gz` -- the Dovecot Lua adapter
- Generate SHA-256 and SHA-512 checksums
- Create GPG-detached signatures (`.asc`) for both tarballs
- Push the tag to `origin`
- Create a GitHub Release with notes extracted from `CHANGELOG.md`
- Upload all artifacts as release assets

## Docker image

The Docker image (`systemli/userli:latest`) is published automatically to Docker Hub on every push to the `main` branch via GitHub Actions.
This is separate from the tarball release process.
