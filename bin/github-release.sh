#!/bin/sh

set -e

# idea taken from the following projects:
# * https://gist.github.com/stefanbuck/ce788fee19ab6eb0b4447a85fc99f447
# * https://github.com/NicoHood/gpgit

if [ -z "$VERSION" ]; then
    printf "Error: environment variable \$VERSION needs to be set\n" >&2
    exit 1
fi

if [ -z "$GITHUB_API_TOKEN" ]; then
    printf "Error: environment variable \$GITHUB_API_TOKEN needs to be set\n" >&2
    exit 1
fi

# set Github variables
gh_group="systemli"
gh_project="userli"
gh_api="https://api.github.com"
gh_repo="$gh_api/repos/${gh_group}/${gh_project}"
gh_tags="$gh_repo/releases/tags/$VERSION"
gh_auth="Authorization: token $GITHUB_API_TOKEN"

# parse CHANGELOG.md
gh_notes="$(awk "/^# $VERSION/{flag=1; next} /^# [0-9]+/{flag=0} flag" CHANGELOG.md | grep '[^[:blank:]]' | awk -vORS='\\n' 1)"

# validate token
curl --output /dev/null --silent --header "$auth" $gh_repo || { printf "Error: Invalid repo, token or network issue\n" >&2; exit 1; }

# create release on Github
api_json=$(printf '{"tag_name": "%s","target_commitish": "%s","name": "%s","body": "%s","draft": false,"prerelease": %s}' "$VERSION" "main" "$VERSION" "$gh_notes" "false")
gh_release="$(curl --silent --proto-redir https --data "$api_json" "$gh_repo/releases" -H "Accept: application/vnd.github.v3+json" -H "$gh_auth")"

# read asset tags
gh_tag_response="$(curl --silent --header "$gh_auth" "$gh_tags")"

# get release id
eval $(printf "$gh_tag_response" | grep -m 1 "id.:" | grep -w id | tr : = | tr -cd '[[:alnum:]]=')
[ "$id" ] || { printf "Error: Failed to get release id for tag: %s\n" "$VERSION"; printf "%s\n" "$gh_tag_response" | awk 'length($0)<100' >&2; exit 1; }

# upload to Github
for ext in "" ".asc" ".sha256" ".sha512"; do
    gh_asset="https://uploads.github.com/repos/${gh_group}/${gh_project}/releases/${id}/assets?name=userli-${VERSION}.tar.gz${ext}"
    curl --silent --proto-redir https "$gh_asset" \
            -H "Content-Type: application/octet-stream" \
            -H "Accept: application/vnd.github.v3+json" \
            -H "$gh_auth" --data-binary @"build/userli-${VERSION}.tar.gz${ext}"
done
