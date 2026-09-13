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

### Promotion and sync PR titles must NOT be conventional commits

This is not cosmetic, and it is the opposite of the rule above.

When GitHub creates a merge commit it writes `Merge pull request #N from
owner/branch` as the subject and **puts the PR title in the body**. release-please
parses the body. So a promotion PR titled `feat: ship the thing` lands on `main`
as a commit whose body is `feat: ship the thing`, and you get a phantom
changelog entry — on top of the real entries from the commits the promotion
actually carried, and possibly a wrong minor bump.

Observed directly in this repo: PR #5 was titled `feat: add health check
endpoint` and merged with a merge commit. Its merge commit `a26d41d` has a
non-conventional subject and the body `feat: add health check endpoint`, and
release-please emitted an entry for it — duplicating the entry from the real
commit `a0893d8` underneath.

So title promotion and sync PRs in plain prose:

- `Promote test to preprod (2026-09-13)`
- `Release candidate: preprod to main`

`sync.yml` names its own PRs `sync: <source> -> <target>`, and `sync` is absent
from `changelog-sections`, so those produce nothing.

The same trap catches feature PRs merged the wrong way: a `feat:`-titled PR
merged with a merge commit instead of a squash yields two entries for one
change. Squash at `feature -> test`, always.

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
| `deploy-feature-env.yml` | PR into `test` | Per-PR feature environment, URL posted as a sticky comment. **Mock.** |
| `pr-title-lint.yml` | PR into `main` | Conventional-commit title on hotfix PRs. |
| `pr-body-type.yml` | PR into `test` or `main` | Exactly one type ticked in the body, and it matches the title prefix. |
| `deploy-production.yml` | Called by `release-please.yml` when a release is cut | Deploys the tagged commit. **Mock.** |
| `release-please.yml` | Push to `main` | Opens/updates the release PR. |
| `sync-main-to-preprod.yml` | Push to `main` | Step 6. |
| `sync-preprod-to-test.yml` | Merge of `sync/main-to-preprod` | Step 7. |
| `merge-method-guard.yml` | Push to `preprod` | Fails if someone squashed. |
| `deploy-preprod.yml` | Push to `preprod` | Deploys the branch tip. **Mock.** |
| `pr-description.yml` | PR into `test`, `preprod` or `main` | Writes the PR body: commits and authors, plus the type checklist on feature PRs. |

### The three environments

| Environment | Deployed by | When | Gated on a release? |
|---|---|---|---|
| feature (`pr-<n>`) | `deploy-feature-env.yml` | PR opened or pushed against `test` | no |
| preprod | `deploy-preprod.yml` | anything merges to `preprod` | no |
| production | `deploy-production.yml` | release-please cuts a version | **yes** |

Only production is gated. preprod exists to be the thing you look at *before*
tagging, so it deploys the branch tip as-is — both the `test -> preprod`
promotion and a hotfix arriving via `sync/main-to-preprod`, since both change
what preprod is. The version it reports is `version.txt` plus a commit sha,
which is deliberately not a release number.

Feature environments are keyed on the pull request number, not the branch name:
the number is the only identifier that survives a force-push or a rename. The
URL goes on the PR as a **sticky** comment, updated in place — `synchronize`
fires on every commit and a fresh comment each time would bury the review.

**Feature environments are never torn down.** That needs a
`pull_request: [closed]` trigger in its own file, for the reason in "One
trigger per workflow" below. Until it exists, they accumulate.

### Nothing production-named runs on a pull request

`deploy-production.yml` has no `pull_request` or `push` trigger at all —
`workflow_call` and `workflow_dispatch` only. The single caller is
`release-please.yml`, behind `release_created == 'true'`.

There was briefly a `deploy-production-preview.yml` that ran on PRs into `main`
to show the payload. It is gone: `pr-description.yml` puts the same
information in the PR body, grouped and with the version impact, which is
better than a job summary — and a check named after production running on a
pull request invites exactly the question "did it just deploy?" every time.

### If no tag appears, nothing ships

release-please cuts the GitHub release, and therefore the tag, on the run
*after* the release PR merges. It finds that merged PR by its
`autorelease: pending` label. If the label was never applied — a token without
permission to label, for instance — it finds nothing, creates nothing, and
exits zero. Green run, no tag.

That used to be silent, and it matters more now that the production deploy is
gated on `release_created`: no tag means production never ships, with nothing
saying so. `release-please.yml` now checks the tag really exists on the remote
and fails the run if it does not.

To recover a release that was merged but never cut: confirm the merged release
PR carries the `autorelease: pending` label, then re-run the workflow from the
Actions tab.

### Promotion PR bodies are generated

A promotion PR shows a **diff**. The question being asked of the approver is
"is this the right release content" — and a diff is the wrong shape for that
question, especially when the promotion carries a dozen commits from several
people.

`pr-description.yml` writes the answer into the body: every commit being
promoted, grouped under the same section names release-please uses, plus the
version bump they imply (`major` / `minor` / `patch` / `none`). Commits that
are not conventional are listed separately under "Not conventional commits",
since those will be invisible in the changelog — that list should normally be
empty, and a surprise in it is worth stopping for.

The block lives between `<!-- pr-description:start -->` and `:end` markers
and only that block is rewritten. Notes you write above or below it survive
every refresh.

### Production deploys only on a cut release

`deploy-production.yml` does not listen for pushes to `main`, and must not.
`main` is pushed **twice** per release — once when the promotion merges, and
again when the release PR merges — so a push trigger deploys production from an
untagged commit before the version bump and changelog exist, then deploys again
a moment later. That is not a hypothetical; it happened here.

release-please.yml calls it instead, gated on
`needs.release-please.outputs.release_created == 'true'`, which is true only on
the run where a version was actually cut. One release, one deploy, of the
commit carrying the tag.

Compare against the string `'true'`, not truthiness: GitHub casts the
non-empty string `"false"` to truthy in an `if:`, so a bare test deploys on
every run.

It is *called* rather than triggered by `on: release` or a tag push because a
release or tag created with `GITHUB_TOKEN` does not trigger another workflow —
GitHub's anti-recursion guard. Such a workflow would simply never fire. Running
in the same job graph avoids the question.

**This gate is only as good as release-please's ability to cut a release.** If
no tag is being created, `release_created` is never `'true'` and production
never deploys — silently. Confirm a tag appears on the remote after a release
PR merges.

### One trigger per workflow

A job skipped by a job-level `if:` still reports a `skipped` check on the pull
request. Two jobs in one workflow -- one for `pull_request`, one for `push` --
therefore put a permanently-skipped entry on every PR, which is noise that
trains people to stop reading the check list.

So: if a job only ever runs for one event, give it its own workflow file and
put the condition in `on:`, not in `if:`. That is why the production deploy is
two files.

Job-level `if:` is still right where the condition genuinely varies per PR and
cannot be expressed as a trigger. `github.head_ref` is the usual case, since
GitHub has no head-branch filter in `on:` -- which is why the title and body
lints show as skipped on `preprod -> main` and `sync/*` PRs. That one is not
fixable by splitting files.

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
