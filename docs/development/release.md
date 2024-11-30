# Creating release tarballs

Release tarballs are the preferred way to install Userli. This page explains how to create them.
<!--more-->

First, you need a [Github API token](https://github.com/settings/tokens).
The token needs the following privileges:

    public_repo, repo:status, repo_deployment

Now, run the Makefile target `release`. It will create a version tag, Github release and
copy the info from `CHANGELOG.md` to the release info.

    $ VERSION=<version> GITHUB_API_TOKEN=<token> GPG_SIGN_KEY="<key_id>" make release

