<!--
PROMOTION PRs — `test` → `preprod`, or `preprod` → `main`:

  Leave this template exactly as it is and don't tick anything.

  The moment you click "Create pull request", a workflow replaces this whole
  description with the commits being promoted, grouped by type, with the
  version bump they imply. An untouched template is discarded; tick a box or
  write anything below and it is kept instead, which is usually not what you
  want here.

  Give the PR a plain-prose title — "Promote test to preprod". NOT a
  conventional commit: GitHub copies the PR title into the merge commit body,
  release-please parses that body, and a `feat:` title here becomes a phantom
  changelog entry.

FEATURE and HOTFIX PRs — into `test`, or a hotfix into `main`:

  Fill this in. These are squashed, so the PR title becomes the commit message
  and is what release-please reads. Tick exactly ONE box and make it match the
  title's prefix; a workflow checks that they agree.
-->

## Type of change

- [ ] `feat` — a new feature
- [ ] `fix` — a bug fix
- [ ] `perf` — a performance improvement
- [ ] `revert` — reverts a previous change
- [ ] `refactor` — neither fixes a bug nor adds a feature
- [ ] `docs` — documentation only
- [ ] `build` — build system or tooling
- [ ] `deps` — dependency updates
- [ ] `ci` — CI configuration
- [ ] `test` — adding or correcting tests
- [ ] `style` — formatting only, no behaviour change
- [ ] `chore` — anything else

## What changed

## Why
