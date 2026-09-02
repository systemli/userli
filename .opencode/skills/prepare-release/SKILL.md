---
name: prepare-release
description: Prepare a Userli release by generating changelog entries from merged PRs, updating CHANGELOG.md and UPGRADE.md, and creating a draft release PR
---

# Prepare Userli Release

You are preparing a new Userli release. Follow these steps precisely.

## Step 1: Extract Version

Extract the target version from the user's prompt (e.g. "Prepare Userli Release 6.2.0" -> `6.2.0`).
If no version is provided, ask the user for the target version.

## Step 2: Determine Last Release

Run:

```bash
gh release list --repo systemli/userli --limit 1 --json tagName,publishedAt
```

This gives you the last release tag and its date.

## Step 3: Collect Merged PRs Since Last Release

Run:

```bash
gh pr list --repo systemli/userli --state merged --base main --search "merged:>YYYY-MM-DD" --limit 100 --json number,title,mergedAt
```

Replace `YYYY-MM-DD` with the `publishedAt` date of the last release (date only, no time).

## Step 4: Filter PRs

Remove any PRs whose title starts with `Prepare Userli Release` — these are previous release preparation PRs and should not appear in the changelog.

## Step 5: Categorize PRs by Gitmoji

Categorize each PR based on the leading emoji in its title into one of three sections.

### Features and Improvements

Emojis: `✨` (new feature), `🚸` (improve UX), `⚡` (performance), `💄` (UI/style), `♿️` (accessibility), `🔥` (remove code/feature)

### Technical Changes

Emojis: `♻️` (refactor), `⬆️` (upgrade dependency), `✅` (tests), `👷` (CI/build), `🗃️` (database), `🌐` (i18n), `📝` (docs)

### Bug Fixes

Emojis: `🐛` (bug fix), `🚑` (critical hotfix)

### Fallback

If a PR title does not start with a recognized emoji, place it under **Features and Improvements**.

### Sorting

Within each section, sort PRs by PR number in **descending** order (newest first).

### Grouping Dependency Update PRs

Automated dependency-bump PRs (Dependabot, Renovate, or similar bots) are often numerous and repetitive. Group them into a single combined entry per dependency ecosystem instead of listing each one separately:

- **Composer dependencies**: combine into one entry titled `⬆️ Update composer dependencies (#<num>, #<num>, ...)`
- **npm dependencies**: combine into one entry titled `⬆️ Update npm dependencies (#<num>, #<num>, ...)`
- **GitHub Actions**: combine into one entry titled `⬆️ Update GitHub Actions (#<num>, #<num>, ...)`

Rules for grouping:

- Only group PRs that are pure automated dependency bumps (e.g. titles like "Bump X from A to B", "Update dependency X", "(deps): Bump the npm-dependencies group ..."). Do NOT group PRs that make other, non-bump changes even if they touch dependency files.
- Within a group's PR number list, order numbers in **descending** order (newest first).
- Place the grouped entry in the **Technical Changes** section.
- CI/workflow changes that are not dependency bumps themselves (e.g. "Add automatic merge for Dependabot PRs", "Reduce update interval to monthly") stay as separate, individual entries — do not fold them into the dependency groups.
- If there's only one dependency-bump PR for an ecosystem, it's fine to still use the grouped title format (e.g. `⬆️ Update composer dependencies (#1349)`).

## Step 6: Update CHANGELOG.md

Read the current `CHANGELOG.md`. Insert a new version block **directly after** the `# Changelog` heading (before any existing version entries).

Use this exact format:

```markdown
## <VERSION> (<YYYY.MM.DD>)

### Features and Improvements

- <emoji> <PR title without emoji prefix> (#<number>)
- ...

### Technical Changes

- <emoji> <PR title without emoji prefix> (#<number>)
- ...

### Bug Fixes

- <emoji> <PR title without emoji prefix> (#<number>)
- ...
```

**Important rules:**

- Use today's date in `YYYY.MM.DD` format (dots, not hyphens)
- Each PR entry keeps its original emoji, followed by the title text, followed by ` (#<number>)`
- If a section has no PRs, **omit** that entire section (heading and entries)
- Ensure a blank line between the version heading and the first section
- Ensure a blank line between sections
- Ensure a blank line after the last entry before the next version heading
- Do NOT duplicate PR entries — if a PR number already exists in the changelog, skip it
- The PR title in the changelog should match the PR title from GitHub exactly (including the emoji prefix)

## Step 7: Update UPGRADE.md

Read the current `UPGRADE.md`. Check if there is an existing section header matching `## Upgrade to <VERSION>` or `## Upgrade from <PREVIOUS_VERSION> or lower`.

- If an **UNRELEASED** section exists (e.g. `## Upgrade to UNRELEASED` or `## Unreleased`), rename it to `## Upgrade to <VERSION>`
- If a section for the target version already exists (e.g. `## Upgrade to 6.2.0`), leave it as-is — it was already prepared in advance
- If no relevant section exists and this is a **major or minor** release (not a patch), ask the user if any upgrade notes are needed
- For **patch releases** (e.g. `6.1.1`), no UPGRADE.md changes are typically needed

Show the user the current UPGRADE.md content at the top and ask if it looks correct before proceeding.

## Step 8: Create Branch, Commit, and PR

1. **Create a new branch** from the current HEAD:
   ```
   Prepare-Userli-Release-<VERSION>
   ```
   Example: `Prepare-Userli-Release-6.2.0`

2. **Stage the changed files** (`CHANGELOG.md` and optionally `UPGRADE.md`)

3. **Commit** with the message:
   ```
   Prepare Userli Release <VERSION>
   ```

4. **Push** the branch to the remote

5. **Create a draft PR** targeting `main`:
   - Title: `Prepare Userli Release <VERSION>`
   - Body: empty (no description needed — the changelog diff speaks for itself)

## Step 9: Report Back

Show the user:

- The PR URL
- A summary of the changelog entries grouped by category
- The number of PRs included
- Any PRs that were skipped and why

## Example

For version `6.1.0` released on `2026.02.10`, the changelog block looks like:

```markdown
## 6.1.0 (2026.02.10)

### Features and Improvements

- 🚸 Improve Error Handling in Dovecot Lua Adapter (#1034)
- ✨ Add configurable Redis cache support via REDIS_URL (#1033)
- ⚡ Add caching for Dovecot userdb lookup API (#1027)

### Technical Changes

- ✅ Add unit tests for 16 previously uncovered classes (#1029)
- 👷 Add Rector CI workflow that comments on PRs with diffs (#1025)
- ♻️ Modernize codebase for PHP 8.4 (#1024)

### Bug Fixes

- 🐛 Fix Xdebug blocking all HTTP requests in dev environment (#1032)
```
