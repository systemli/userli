#!/bin/sh

# configuration
vagrant="yes" # run build process in vagrant

# idea taken from the following projects:
# * https://gist.github.com/stefanbuck/ce788fee19ab6eb0b4447a85fc99f447
# * https://github.com/NicoHood/gpgit

if [ -z "$1" ]; then
    printf "Error: release version required as first argument\n" >&2
    exit 1
fi

if [ -z "$GPG_SIGN_KEY" ]; then
    printf "Error: environment variable \$GPG_SIGN_KEY needs to be set\n" >&2
    exit 1
fi

if [ -z "$GITHUB_API_TOKEN" ]; then
    printf "Error: environment variable \$GITHUB_API_TOKEN needs to be set\n" >&2
    exit 1
fi

# set release variables
version="$1"
today="$(date +%Y.%m.%d)"

# set Github variables
gh_group="systemli"
gh_project="user-management"
gh_api="https://api.github.com"
gh_repo="$gh_api/repos/${gh_group}/${gh_project}"
gh_tags="$gh_repo/releases/tags/$version"
gh_auth="Authorization: token $GITHUB_API_TOKEN"
curl_args="--location --remote-header-name --remote-name #"

# parse CHANGELOG.md
if ! grep -qx "# $version (.*)" CHANGELOG.md; then
    printf "Error: Couldn't find section for version %s in CHANGELOG.md\n" "$version" >&2
    exit 1
elif ! grep -qx "# $version ($today)" CHANGELOG.md; then
    date="$(sed -n "s/# $version (\(.*\))/\1/p" CHANGELOG.md)"
    printf "Error: Release date \"%s\" != \"%s\" (today) for version %s in CHANGELOG.md\n" "$date" "$today" "$version" >&2
    exit 1
fi

gh_notes="$(awk "/^# $version/{flag=1; next} /^# [0-9]+/{flag=0} flag" CHANGELOG.md | grep '[^[:blank:]]' | awk -vORS='\\n' 1)"

# make a gpg-signed tag for the release
git tag --sign --message "Release $version" "$version"

# create release tarball
tarball="build/user-management-$(git --no-pager describe --tags --always).tar.gz"
build_cmd="make release"
if [ "$vagrant" = "yes" ]; then
	(cd vagrant/;
	 vagrant up;
	 vagrant ssh -c 'tempdir="$(mktemp -d)";
	                 git clone /vagrant "$tempdir";
			 (cd "$tempdir";
                          make release;
                          cp -a build/user-management* /vagrant/build/);
                         rm -r "$tempdir"')
else
    make release
fi
if [ ! -f "$tarball" ]; then
    printf "Error: release tarball %s not created\n" "$tarball" >&2
    exit 1
fi

# gpg-sign release tarball
gpg -u ${GPG_SIGN_KEY} --output "${tarball}.asc" --armor --detach-sign --batch --yes "$tarball"

# validate token
curl --output /dev/null --silent --header "$auth" $gh_repo || { printf "Error: Invalid repo, token or network issue\n" >&2; exit 1; }

# push git tag
git push origin "refs/tags/${version}" >/dev/null

# create release on Github
api_json=$(printf '{"tag_name": "%s","target_commitish": "%s","name": "%s","body": "%s","draft": false,"prerelease": %s}' "$version" "master" "$version" "$gh_notes" "false")
echo "api_json: $api_json"
gh_release="$(curl --silent --proto-redir =https --data "$api_json" "$gh_repo/releases" -H "Accept: application/vnd.github.v3+json" -H "$gh_auth")"

# read asset tags
gh_response="$(curl --silent --header "$auth" "$gh_tags")"

# get release id
eval $(printf "$gh_response" | grep -m 1 "id.:" | grep -w id | tr : = | tr -cd '[[:alnum:]]=')
[ "$id" ] || { printf "Error: Failed to get release id for tag: %s\n" "$version"; printf "%s\n" "$gh_response" | awk 'length($0)<100' >&2; exit 1; }

# upload to Github
for ext in "" ".asc" ".sha256" ".sha512"; do
    gh_asset="https://uploads.github.com/repos/${gh_group}/${gh_project}/releases/${id}/assets?name=$(basename ${tarball}${ext})"
    curl --silent --proto-redir =https "$gh_asset" \
            -H "Content-Type: application/octet-stream" \
            -H "Accept: application/vnd.github.v3+json" \
            -H "$gh_auth" --data-binary @"${tarball}${ext}"
done
