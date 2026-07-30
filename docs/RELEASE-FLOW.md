# CWMLivingWord — Branching & Release Flow

**Repository:** [`Joomla-Bible-Study/CWMLivingWord`](https://github.com/Joomla-Bible-Study/CWMLivingWord)
**Applies to:** `CWMLivingWord`, `CWMScriptureLinks`, `lib_cwmscripture`, `cwm-build-tools`, `CWMPrayerCenter`, `Proclaim`
**Proclaim differs in one step only** — see [§8](#8-proclaim)
**Status:** Draft for review
**Last updated:** 2026-07-30

> This document records the branching and release conventions for the main-based CWM repositories. It exists because the conventions were previously implicit, which let two avoidable problems through — a squash-merged branch that nearly became a revert PR, and a package that shipped for five releases with no update server at all.

---

## At a glance

| Question | Answer |
|---|---|
| Where does work happen? | Short-lived branches off `origin/main` |
| How does it land? | Squash merge, branch auto-deleted |
| How do we ship unstable builds? | A version **suffix** (`-alpha1`/`-beta1`/`-rc1`), not a branch |
| Who sees an unstable build? | Only sites that lowered **Minimum Stability** in Joomla |
| When do we add a long-lived branch? | Only when two majors are supported at once |

---

## 1. Branching

`main` is always releasable. There is no `develop` branch and no release branches.

### Naming

`type/<issue>-<slug>`, where `type` matches the conventional-commit type the work will land as:

```
feat/55-group-invitation-flow
fix/ars-update-server
chore/bump-lib-submodule
ci/use-reusable-workflow
docs/release-flow
```

The full prefix list lives in [CONTRIBUTING.md](../CONTRIBUTING.md#branch-naming-conventions) — this document owns the rules, that one owns the contributor-facing detail.

### Rules

1. **Branch from `origin/main`, never from local `main`.**
   ```bash
   git fetch origin && git switch -c fix/thing origin/main
   ```
   Local `main` goes stale silently. A branch cut from a six-commit-old local `main` still merges cleanly, so nothing warns you.

2. **One branch → one PR → one squash commit → deleted.**

3. **Never reuse a branch after its PR merges.** After a squash merge the branch still *looks* several commits ahead of `main`, because the squash commit has a different SHA. Reusing it proposes reverting whatever landed after it. See [Traps](#5-traps).

4. **Don't sweep unrelated changes into a PR.** Commit with an explicit pathspec when the tree is dirty:
   ```bash
   git commit -F msg.txt -- path/to/file.xml
   ```

---

## 2. Repository settings

These make the convention self-enforcing rather than a thing to remember.

| Setting | Value | Why |
|---|---|---|
| Allow squash merge | ✅ | The only strategy we use |
| Allow merge commits | ❌ | Keeps history linear and predictable for `cwm-changelog` |
| Allow rebase merge | ❌ | Same |
| Auto-delete head branches | ✅ | Prevents the stale-branch trap outright |
| Branch protection on `main` | Require PR + the "PHP Lint & Tests" check | Blocks direct pushes and red merges |

Approvals are deliberately **not** required — the maintainer team is too small for that to do anything but block.

---

## 3. Release channels

**The channel is the version string, not a branch.** `cwm-release` derives everything from the version you pass it:

```
composer release -- 5.6.0-beta1
   → cwm_maturity_for_version → "beta"
   → GitHub release flagged "Pre-release"
   → ARS maturity=beta → <tags><tag>beta</tag></tags> in the update stream
   → Joomla Updater hides it from every site on default settings
```

| Suffix | ARS maturity | Who is offered the update |
|---|---|---|
| `-alpha1` | `alpha` | Sites with Minimum Stability ≤ Alpha |
| `-beta1` | `beta` | Sites with Minimum Stability ≤ Beta |
| `-rc1` | `rc` | Sites with Minimum Stability ≤ RC |
| *(none)* | `stable` | Everyone |

The gate is core Joomla, not ARS: `Updater::findUpdates()` defaults to `STABILITY_STABLE`, and com_installer's **Minimum Stability** option defaults to `4` (Stable). A site only sees pre-releases if its admin deliberately opts in.

**One stream serves both channels.** Pre-releases go to the same ARS stream (LivingWord = stream 6, category 7). Do not create a second stream, package, or branch for unstable builds.

---

## 4. The release cycle

### When to use which rung

| Change | Ladder |
|---|---|
| Patch — bugfixes only | Straight to stable, or one `-rc1` if it touches install/update paths |
| Minor — new features, b/c safe | `-beta1` → `-rc1` → stable |
| Minor with a schema migration | `-alpha1` → `-beta1` → `-rc1` → stable |
| Major — b/c breaks | Alphas while it takes shape, then beta → rc → stable |

Anything that ships a `.sql` migration or an install-script change earns at least one pre-release. Those are the failures that can't be fixed forward on a live site.

Soak an RC for **5–14 days** against `j5-dev` and `j6-dev` before dropping the suffix.

### Worked example — 5.6.0

```bash
# feature-complete on main
composer release -- 5.6.0-beta1 --dry-run   # inspect the 9-step plan first
composer release -- 5.6.0-beta1

# ...soak, fix forward on main, re-cut as needed...
composer release -- 5.6.0-rc1

# ...soak...
composer release -- 5.6.0
```

Every tag is cut from `main`. `--dry-run` walks the whole pipeline and writes nothing — use it the first few times.

### `versions.json`

`build/versions.json` is the source of truth for in-progress work:

- `current` — last stable release
- `active_development` — the version to use for `@since` tags and migrations right now
- `next.patch` / `next.minor` / `next.major` — the buckets

At PR time, decide which bucket a change belongs to. That is the same discipline Joomla gets from targeting different branches, without the branches.

---

## 5. Traps

Each of these has bitten this project.

**Squash + surviving branch.** After a squash merge, `git branch -d` refuses with "not fully merged" and the branch looks ahead of `main`. Verify content is on `main` (`git diff main <branch> -- <file>` is empty, or differs only by later changes), then `-D`. Auto-delete-on-merge prevents the remote half.

**Maturity typos publish as beta.** ARS's `ReleaseTable::check()` silently rewrites any unrecognized maturity to **`beta`** — no error. And `cwm_maturity_for_version` only matches lowercase `-alpha` / `-beta` / `-rc`. A `-Beta1` or `-b1` suffix makes a release invisible to normal sites. Use exactly those three spellings.

**ARS update URLs need `task=stream`.** Without it ARS falls back to `task=all` and returns an `<extensionset>` *collection* index instead of an `<updates>` document, and `type="extension"` update servers can't parse it. The correct form:

```
https://www.christianwebministries.org/index.php?option=com_ars&view=update&task=stream&format=xml&id=<STREAM_ID>&dummy=extension.xml
```

`dummy=extension.xml` is ignored by ARS; it exists for proxies that sniff the URL extension.

**ARS `element` must match `#__extensions.element` exactly.** A trailing space in the update stream's element field will not match under MySQL 8's default `utf8mb4_0900_ai_ci` (NO PAD) collation, and the update silently never appears. This field is **not reachable via the API** — `/v1/ars/updatestreams` returns 404 on every verb, because `plg_webservices_ars` never registers the route. It must be fixed in ARS admin.

**Publish the GitHub asset, not a local rebuild.** `cwm-ars-publish` computes checksums from the file you hand it. Download the release asset so ARS records the checksum of what users actually get.

---

## 6. Cross-repo ordering

The submodule chain is strictly ordered and cannot be parallelized:

```
lib_cwmscripture  →  CWMScriptureLinks  →  CWMLivingWord / Proclaim
```

1. Land and release `lib_cwmscripture`.
2. Bump the submodule pointer in `CWMScriptureLinks` (`chore/bump-lib-submodule`).
3. Consumers pick it up on their next build — LivingWord fetches the latest `pkg_cwmscripture` release at build time via `build/fetch_dependencies.php`.

A fix in the library is **not** live for consumers until step 2 happens, even though it's on all three `main` branches.

---

## 7. When to add a long-lived branch

One case only: **6.0 has shipped and 5.x still needs patches.**

Then cut `support/5.x` from the last 5.x tag, backport there, and release 5.6.1 from it. Borrowing Joomla's discipline: fix in the **oldest** supported line and merge *forward*, rather than fixing on `main` and cherry-picking back. Forward merges are routine; backports are an afterthought and get dropped.

Do not create this branch preemptively.

Proclaim already does this correctly: `9.x` and `10.x` sit dead at 2,286 and 1,633 commits behind, as honest markers of lines that were once supported. That is the pattern, not clutter.

---

## 8. Proclaim

Proclaim follows everything above. Its trunk is named `development` rather than `main`, and PRs squash-merge into it with the same conventional, issue-linked commits.

**The one difference — and the one open item.** At release time Proclaim merges `development` into `main`, then commits the version bump and changelog *on `main`* and tags there. Because those commits never return, the branches drift permanently:

```
main   18 commits ahead of development   (5 releases of bump/changelog/merge)
       1 commit behind                   (a hand-reconciled versions.json)
```

Every release widens the gap by three or four commits. `main` isn't serving as a separate supported line — `9.x`/`10.x` do that — so it only relocates the version bump, at the cost of permanent divergence, a manual backflow, and a branch name that invites contributors to treat it as trunk.

**Preferred:** bump and changelog on `development`, tag there, then fast-forward `main` to the tag. `main` becomes a pointer that cannot diverge, and the name stays available for anyone who expects it.

This is a change to Proclaim's release step, not to its contribution flow, which needs nothing.

---

## Appendix: what we borrowed from Joomla core

Joomla runs four live branches — `5.4-dev`, `6.1-dev`, `6.2-dev`, `7.0-dev` — with no `main`. The branch you target is decided by what *kind* of change it is: bugfixes to the current 5.x line, b/c-safe features to the next minor, breaking changes to the next major. Fixes then flow oldest → newest via routine "upmerge" PRs, 2–4× a month.

**Adopted:** the RC rung (Joomla won't ship a *patch* without an RC — tags run `6.1.2-rc1 → rc2 → rc3 → 6.1.2`), the "what kind of change is this" question at PR time, and the forward-merge direction for the day we need it.

**Not adopted:** the four-branch structure itself. That exists to support two majors concurrently across hundreds of volunteers, and its cost is continuous upmerging plus four CI matrices. We support one line, so the maturity suffix gives us the same safety for none of that overhead.

The shape of the difference:

- **Joomla:** branch-per-line + maturity-per-tag
- **CWM:** trunk + maturity-per-tag

When we support two majors, we add the branch axis and nothing else changes.
