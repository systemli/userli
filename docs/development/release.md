# Creating release tarballs

Release tarballs are the preferred way to install Userli. This page explains how to create them.
<!--more-->

First, you'll need a [Github API token](https://github.com/settings/tokens).
The token needs the following privileges:

```text
public_repo, repo:status, repo_deployment
```

Now, execute the following script. It will create a version tag, release and
copy the info from `CHANGELOG.md` to the release info.

```shell
GITHUB_API_TOKEN=<token> GPG_SIGN_KEY="<key_id>" ./bin/github-release.sh <version>
```

