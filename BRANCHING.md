# Branching and release flow

## The one rule

**`feature → test` is the only squash in this repository. Everything above it is a merge commit.**

Squashing rewrites commits into new ones. After a squash between two long-lived
branches, git no longer sees a shared ancestor even though the code is
identical — the branches are permanently divergent, and every future merge
re-conflicts on the same content. That single behaviour is the source of
essentially every problem this setup exists to fix.

| Merge | Method | Why |
|---|---|---|
| `feature → test` | **Squash** | Produces one commit per feature. This commit is what release-please reads. |
| `test → preprod` | **Merge commit** | Preserves the individual feature commits and the shared ancestor. |
| `preprod → main` | **Merge commit** | Same. |
| `hotfix → main` | **Squash** | Same atom shape as a feature. Must be titled `fix: …`. |
| `sync/main-to-preprod → preprod` | **Merge commit** | Same. |
| `sync/preprod-to-test → test` | **Merge commit** | Same. |

Never rebase between long-lived branches.

## PR titles

PRs into `test` and `main` are squashed, so the **PR title becomes the commit
message**. It must be a conventional commit: `feat: …`, `fix: …`, `perf: …`,
`chore: …`. This is enforced by `pr-title-lint.yml`.

Set the repository to **use the PR title as the squash commit message**
(Settings → General → Pull Requests), otherwise GitHub will substitute the
branch name and release-please will see nothing.

Promotion and sync PR titles are free-form — they become merge commits and are
never changelog input.

## The flow

```
feature ──squash──▶ test ──merge──▶ preprod ──merge──▶ main ──▶ release-please ──▶ tag
                     ▲                  ▲                               │
                     └──── sync ────────┴──────── sync ─────────────────┘
hotfix ─────────────────────────────────────squash──▶ main
```

1. `feature → test` — squash, conventional-commit title.
2. `test → preprod` — merge commit. The approval gate here is *"is this the
   right release content"*, not code review; the code was already reviewed at
   step 1.
3. `preprod → main` — merge commit.
4. Push to `main` → `release-please.yml` opens or updates the release PR
   (`CHANGELOG.md` + `version.txt` + `config/version.php`).
5. Merge the release PR → tag + GitHub release → deploy.
6. Push to `main` → `sync-main-to-preprod.yml` opens `sync/main-to-preprod`.
7. Merge of that → `sync-preprod-to-test.yml` opens `sync/preprod-to-test`.
8. Merge of that → done. `test` now matches production.

**Hotfixes need no special path.** Branch off `main`, PR into `main` with a
`fix:` title, squash. Step 4 picks it up; steps 6–7 carry it back down. There
is nothing to remember and nothing to ask a branch owner for.

## Who approves the sync PRs

The sync workflow reads the commits being propagated, resolves their authors,
and requests review from them. If you wrote the hotfix, you are asked to
confirm it should land on `preprod` and then on `test`.

This deliberately routes around the branch owner. Turn **off** "Require review
from Code Owners" on `preprod` and `test` and keep a plain 1-approval
requirement — otherwise propagation is blocked on one person's availability,
which is the problem this replaces.

Two known gaps:

- **The PAT owner cannot review a PR the PAT opened.** Use a machine account
  for `SYNC_TOKEN`. If the PAT belongs to a developer, their own hotfix syncs
  will have no reviewer and the workflow logs a warning.
- **Commits whose author email is not linked to a GitHub account resolve to
  nobody.** The workflow warns; the PR sits unassigned.

## Workflows

| Workflow | Trigger | What it does |
|---|---|---|
| `test.yml` | PR into `test` | Gates on PR alignment, then runs the suite. **Mock.** |
| `pr-title-lint.yml` | PR into `main` | Conventional-commit title on hotfix PRs. |
| `pr-body-type.yml` | PR into `test` or `main` | Exactly one type ticked in the body, and it matches the title prefix. |
| `deploy-production.yml` | PR into `main`, and push to `main` | Previews the payload on the PR; deploys after merge. **Mock.** |
| `release-please.yml` | Push to `main` | Opens/updates the release PR. |
| `sync-main-to-preprod.yml` | Push to `main` | Step 6. |
| `sync-preprod-to-test.yml` | Merge of `sync/main-to-preprod` | Step 7. |
| `merge-method-guard.yml` | Push to `preprod` | Fails if someone squashed. |

### The type checkbox

`.github/pull_request_template.md` carries a "Type of change" checklist.
`pr-body-type.yml` requires exactly one box ticked, and requires it to match
the title prefix.

The checkbox tells the machine nothing the title does not already say — the
title is what lands on the branch and what release-please parses. It exists for
the author: ticking `feat` and then typing `chore: …` is the moment you catch a
change that was about to be left out of the release notes. So the cross-check
is the feature, not the checkbox.

Backticks around the type are required, and only the section under
`## Type of change` is read — otherwise a ticked box in some unrelated
checklist ("- [x] test coverage added") would register as the type `test`.

### What "aligned" means for a `feature -> test` PR

`test.yml` will not run the suite unless both hold:

1. **The PR title is a Conventional Commit.** This is the only squash boundary,
   so the title becomes the commit subject on `test`, and that commit is the
   atom release-please later reads. A bad title produces a change that is
   invisible in the release notes, permanently.
2. **The branch contains the current tip of `test`.** A branch that is behind
   is being tested against a codebase that is not what ships, so a green run
   means nothing.

`sync/*` PRs bypass the gate -- they are merge commits, their titles are never
changelog input, and they carry content `test` lacks by definition. Their tests
still run.

## Conflicts

Branch protection means a conflict cannot be fixed on `preprod` or `test`
directly. The `sync/*` branch is where a human resolves it — the same pattern
as the old `update-preprod-from-test` branch, which was always the right shape.

While a sync PR is open, the workflow will **not** touch its branch on later
runs. It refuses to move a branch someone may be resolving conflicts on. Once
merged, the next trigger opens a fresh one.

Conflicts should be rare once nothing above `feature → test` is squashed. The
exception is files that differ between branches *by design* — see below.

## Files that differ per branch

Environment config, connection settings and `CODEOWNERS` that legitimately
differ per branch will conflict on every promotion, forever, and no merge
strategy fixes that because the difference is intentional.

The fix is to stop having per-branch file content:

- Move environment config out of the branch — runtime env vars, GitHub
  Environments, or one file keyed by environment name and identical on every
  branch.
- Keep a single `CODEOWNERS` identical on all branches. Since code-owner review
  is off for `preprod`/`test` anyway, there is no reason for it to differ.

Until that is done, expect sync PRs to need manual conflict resolution on
exactly those files.

## Versioning

`simple` release-please strategy. `version.txt` is the source of truth;
`config/version.php` is bumped in the same release PR via `extra-files` so the
application can report its own version.

`CHANGELOG.md` is owned by the bot. **Do not hand-edit it.** Hand edits are what
made it conflict on every promotion.

## Required repository setup

- Secret `SYNC_TOKEN`: a PAT (`repo` + `workflow` scope), ideally on a machine
  account. The default `GITHUB_TOKEN` will not work — events it creates do not
  trigger further workflows, so the step 6 → step 7 chain stops silently.
- Allow both squash merges and merge commits.
- Squash commit message = **pull request title**.
- `preprod` and `test`: require a PR, require 1 approval, **disable** code-owner
  review.
- `main`: require a PR, require 1 approval.
