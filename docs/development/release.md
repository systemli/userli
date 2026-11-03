# Release

Release tarballs are the preferred way to distribute Userli.
Releases are drafted and built automatically on GitHub; a maintainer
rebuilds them locally and only signs and publishes them if the local build is
bit-for-bit identical. The GPG signing key never leaves the maintainer's machine.

## How it works

1. Every push to `main` updates the **release draft** via
   [Release Drafter](https://github.com/release-drafter/release-drafter)
   (`.github/workflows/release-drafter.yml`).
   The release notes are generated from the merged pull requests.
2. A second job builds the release tarballs with `make release-container` at
   the exact commit of the draft and attaches them to the draft:
    - `userli-<version>.tar.gz` – the main application
    - `userli-dovecot-adapter-<version>.tar.gz` – the Dovecot Lua adapter
    - `.sha256` and `.sha512` checksums for both
    - `build-info.json` with version and commit
3. A maintainer runs `bin/release.sh`, which rebuilds the tarballs locally,
   compares the checksums, signs them, creates a signed tag and publishes
   the release.

## Pull request labels

Pull requests are labeled automatically by the gitmoji at the start of their
title. The labels decide the section in the release notes and the next version:

| Label | Gitmoji | Section | Version bump |
|-------|---------|---------|--------------|
| `breaking` | – (set manually) | Features and Improvements | major |
| `enhancement` | ✨ 🚸 ⚡ 💄 ♿️, and anything without a rule | Features and Improvements | minor |
| `security` | 🔒 🔐 | Security | patch |
| `bug` | 🐛 🚑 | Bug Fixes | patch |
| `technical dept` | ♻️ 🗃️ 🌐 🔧 🔥 | Technical Changes | patch |
| `documentation` | 📝 | Technical Changes | patch |
| `testing` | ✅ | Technical Changes | patch |
| `dependencies` | ⬆️, `Bump …` | Maintenance | patch |
| `github_actions` | 👷 💚 | Maintenance | patch |

A pull request with several of these labels is listed only in the first
matching section, in the order of the table. For example, a feature that also
updates the docs (`enhancement`, `documentation`) or touches security
(`enhancement`, `security`) is listed under Features and Improvements, a
security fix that is also labeled `bug` under Security. Add `enhancement`
manually to a 🔥 pull request that removes a user-facing feature.
The highest version bump of all pull requests wins.

Fix labels on the pull request if the automatic choice is wrong; the draft is
updated with the next push to `main`.

Changes that require manual steps from operators belong in `UPGRADE.md`.
Add them in the pull request that introduces the change.

## Prerequisites

- `git`, `gh` (logged in with write access to the repository), `gpg`, `jq`,
  `make`, `shasum`
- `podman` or `docker`
- a GPG key for signing the tag and the checksums

## Create the release

Check the release draft on GitHub (version, release notes), then run:

```shell
GPG_SIGN_KEY="<key_id>" bin/release.sh
```

The script will:

- find the release draft and download its assets
- check that the draft commit is on `main` and matches `build-info.json`
- rebuild the tarballs from that commit in the pinned release container
- compare the local checksums with the draft and verify the draft tarballs
- sign the `.sha256` and `.sha512` files (`.asc`)
- create and push a signed tag `<version>`
- upload the signatures and publish the release

Use `bin/release.sh --dry-run` to only rebuild and compare, without signing or
publishing.

Publishing the release triggers `.github/workflows/ansible-role-bump.yml`,
which opens a pull request in
[systemli/ansible-role-userli](https://github.com/systemli/ansible-role-userli)
that bumps `userli_version` and adds the sha512 checksum of the tarball.
Pre-releases are skipped. To run it again for a version, start the workflow
manually with that version.

## Reproducible builds

`make release-container` builds the tarballs in a container defined in
`docker/release/Dockerfile`. The base images are pinned by digest and the
tarballs are created with normalized metadata (sorted entries, fixed
timestamps, owner and permissions), so that every build of the same commit
produces identical files on any machine and architecture.

If `bin/release.sh` reports a mismatch, do not publish. Build the commit twice
and compare the tarballs, for example with
[diffoscope](https://diffoscope.org/):

```shell
podman run --rm -v "$PWD:/data" registry.salsa.debian.org/reproducible-builds/diffoscope \
    /data/a/userli-<version>.tar.gz /data/b/userli-<version>.tar.gz
```

## Docker image

The Docker image (`systemli/userli:latest`) is published automatically to Docker Hub on every push to the `main` branch via GitHub Actions.
This is separate from the tarball release process.
