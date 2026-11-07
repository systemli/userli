#!/bin/sh
#
# Verify, sign and publish the current release draft.
#
# The draft release (maintained by Release Drafter) carries tarballs and
# checksums built by GitHub Actions. This script rebuilds the tarballs
# locally from the same commit in the pinned release container and only
# signs and publishes the release if the checksums are identical.
#
# Usage: GPG_SIGN_KEY=<key_id> bin/release.sh [--dry-run]

set -eu

repo="${GH_REPO:-systemli/userli}"
dry_run=false

case "${1:-}" in
    --dry-run) dry_run=true ;;
    "") ;;
    *) printf "Usage: GPG_SIGN_KEY=<key_id> %s [--dry-run]\n" "$0" >&2; exit 1 ;;
esac

die() {
    printf "Error: %s\n" "$*" >&2
    exit 1
}

if [ "$dry_run" = false ] && [ -z "${GPG_SIGN_KEY:-}" ]; then
    die "environment variable \$GPG_SIGN_KEY needs to be set"
fi

for tool in git gh gpg jq make shasum; do
    command -v "$tool" >/dev/null 2>&1 || die "$tool is required but not installed"
done
command -v podman >/dev/null 2>&1 || command -v docker >/dev/null 2>&1 ||
    die "podman or docker is required but not installed"

cd "$(git rev-parse --show-toplevel)"

# Find the release draft
drafts="$(gh api "repos/$repo/releases" --jq '[.[] | select(.draft)]')"
[ "$(printf "%s" "$drafts" | jq length)" -eq 1 ] || die "expected exactly one release draft in $repo"
release_id="$(printf "%s" "$drafts" | jq -r '.[0].id')"
version="$(printf "%s" "$drafts" | jq -r '.[0].tag_name')"
commit="$(printf "%s" "$drafts" | jq -r '.[0].target_commitish')"
printf "Release draft: %s (commit %s)\n" "$version" "$commit"

printf "%s" "$version" | grep -Eqx '[0-9]+\.[0-9]+\.[0-9]+' || die "unexpected version \"$version\""
printf "%s" "$commit" | grep -Eqx '[0-9a-f]{40}' || die "draft target \"$commit\" is not a commit SHA"
if git rev-parse --verify --quiet "refs/tags/$version" >/dev/null; then
    die "tag $version already exists locally"
fi

# Below $HOME, so that podman machine on macOS can mount it into the container
cache_dir="${XDG_CACHE_HOME:-$HOME/.cache}"
mkdir -p "$cache_dir"
work="$(mktemp -d "$cache_dir/userli-release.XXXXXX")"
cleanup() {
    git worktree remove --force "$work/src" >/dev/null 2>&1 || true
    rm -rf "$work"
}
trap cleanup EXIT INT TERM

# Download the artifacts built by GitHub Actions
mkdir "$work/draft"
gh release download "$version" --repo "$repo" --dir "$work/draft"
[ -f "$work/draft/build-info.json" ] || die "build-info.json missing in release draft"
[ "$(jq -r .commit "$work/draft/build-info.json")" = "$commit" ] ||
    die "build-info.json commit does not match the draft target, wait for the build to finish"
[ "$(jq -r .version "$work/draft/build-info.json")" = "$version" ] ||
    die "build-info.json version does not match the draft, wait for the build to finish"

# The commit must be part of main
git fetch --quiet origin main
git merge-base --is-ancestor "$commit" origin/main || die "commit $commit is not on origin/main"

# Rebuild locally from the same commit
printf "Rebuilding %s locally ...\n" "$version"
git worktree add --quiet --detach "$work/src" "$commit"
(cd "$work/src" && make release-container VERSION="$version") > "$work/build.log" 2>&1 ||
    { tail -n 30 "$work/build.log" >&2; die "local build failed, see output above"; }

# Compare checksums and verify the draft tarballs against the local ones
local_build="$work/src/build"
mismatch=false
for file in "$local_build"/*.sha256 "$local_build"/*.sha512; do
    name="$(basename "$file")"
    if cmp -s "$file" "$work/draft/$name"; then
        printf "  match:    %s\n" "$name"
    else
        printf "  MISMATCH: %s\n" "$name" >&2
        mismatch=true
    fi
done
for name in $(cd "$work/draft" && ls -- *.sha256 *.sha512); do
    [ -f "$local_build/$name" ] || { printf "  UNEXPECTED: %s\n" "$name" >&2; mismatch=true; }
done
if [ "$mismatch" = true ]; then
    die "local build differs from the release draft, compare the tarballs with diffoscope"
fi
for file in "$local_build"/*.sha256 "$local_build"/*.sha512; do
    (cd "$work/draft" && shasum -c "$file" >/dev/null) || die "draft tarball does not match $(basename "$file")"
done
printf "Local build is identical to the release draft.\n"

if [ "$dry_run" = true ]; then
    printf "Dry run, not signing or publishing.\n"
    exit 0
fi

# Sign the checksum files
for file in "$local_build"/*.sha256 "$local_build"/*.sha512; do
    gpg --local-user "$GPG_SIGN_KEY" --armor --detach-sign --batch --yes --output "$file.asc" "$file"
done

# Make sure the draft did not move while we were building
current="$(gh api "repos/$repo/releases/$release_id" --jq '[.draft, .tag_name, .target_commitish] | join(" ")')"
[ "$current" = "true $version $commit" ] || die "release draft changed during verification, run again"

# Signed tag, signatures, publish
git tag --sign --local-user "$GPG_SIGN_KEY" --message "Release $version" "$version" "$commit"
git push --quiet origin "refs/tags/$version"
gh release upload "$version" --repo "$repo" "$local_build"/*.asc
gh release edit "$version" --repo "$repo" --draft=false --latest

printf "Published https://github.com/%s/releases/tag/%s\n" "$repo" "$version"
