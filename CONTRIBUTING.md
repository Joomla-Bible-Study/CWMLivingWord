# Contributing to CWMLivingWord

Thank you for helping improve the CWM LivingWord Bible reading component!
This is a ministry project maintained by [Christian Web Ministries](https://christianwebministries.org).

## Ways to contribute

- **Report bugs** — Use the [Bug Report](.github/ISSUE_TEMPLATE/bug_report.yml) issue template
- **Request features** — Start a [Discussion](../../discussions/categories/feature-ideas) before opening an issue
- **Church admin feedback** — Use the [Church Admin Request](.github/ISSUE_TEMPLATE/church_admin_request.yml) template
- **Code contributions** — See the development workflow below
- **Documentation** — Improvements to README, inline docs, or discussions

## Development setup

See [README.md](README.md) for full setup instructions. Quick start:

```bash
composer install
composer setup       # configure Joomla paths and DB credentials
composer symlink     # symlink to your local Joomla installation
composer verify      # register extensions in Joomla database
```

## Development workflow

1. Fork the repository (outside contributors) — maintainers branch directly
2. Create a branch from `origin/main`:
   ```bash
   git fetch origin
   git switch -c feat/your-feature-name origin/main
   ```
   Branch from `origin/main`, not your local `main` — a stale local branch point still merges cleanly, so nothing warns you.
3. Make your changes, following the code style guidelines below
4. Write or update tests if applicable
5. Run the full check before pushing:
   ```bash
   composer check    # syntax + lint + tests
   ```
6. Open a pull request against `main` using the PR template

PRs are squash-merged and the branch is deleted. Don't reuse a branch after its PR merges — see [docs/RELEASE-FLOW.md](docs/RELEASE-FLOW.md#1-branching) for why.

## Where does my change go?

Everything targets `main`. What changes is which *release* your work ships in — decided by the kind of change it is, and tracked in `build/versions.json`:

| Your change | Ships in | Bucket |
|-------------|----------|--------|
| Bug fix, no behavior change beyond the fix | Next patch | `next.patch` |
| New feature, backward compatible | Next minor | `next.minor` |
| Anything that breaks backward compatibility | Next major | `next.major` |

Two rules worth knowing before you start:

- **Schema changes ship with a pre-release.** If your PR adds a file under `admin/sql/updates/` or touches the install script, the release carrying it goes out as `-beta1` or `-rc1` first. Migrations are the one thing that can't be fixed forward on a live site.
- **Breaking changes need discussion first.** Open a [Discussion](../../discussions) before building — they may need to wait for the next major.

The full release process is in [docs/RELEASE-FLOW.md](docs/RELEASE-FLOW.md).

## Branch naming conventions

`<type>/<issue-number>-<short-slug>`, where `<type>` matches the commit type your work will land as — e.g. `feat/55-group-invitation-flow`, `fix/85-scripture-registration`.

| Prefix | Use for |
|--------|---------|
| `feat/` | New functionality |
| `fix/` | Bug fixes |
| `refactor/` | Code improvement, no behavior change |
| `docs/` | Documentation only |
| `chore/` | Tooling, CI, infrastructure |
| `ci/` | Workflow and pipeline changes |
| `test/` | Test coverage additions |

## Commit message format

We use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add completion checkbox to home view
fix: correct day calculation when dateoffset is negative
refactor: extract email sending to dedicated service class
docs: update README with API.Bible configuration
chore: add issue templates to .github
```

Types: `feat`, `fix`, `refactor`, `docs`, `test`, `chore`, `style`

## Code style

- **PHP:** PSR-12 via `.php-cs-fixer.dist.php` — run `composer lint:fix` to auto-correct
- **PHP indent:** 4 spaces
- **JS/CSS/YAML/JSON indent:** 2 spaces
- **Line endings:** Unix (LF)
- **Encoding:** UTF-8, no BOM
- **PHP 8.3+ features** are welcome (readonly, match, enums, etc.)
- **Joomla naming:** Follow Joomla 5/6 conventions (namespaced MVC, DI container, PSR-4)

## Database changes

If your PR modifies the database schema:

1. Update `admin/sql/install.mysql.utf8.sql` with the complete current schema
2. Add an update script to `admin/sql/updates/` named for the component version (e.g., `5.1.0.sql`)
3. Test both fresh install and upgrade paths
4. Note schema changes clearly in your PR description

## Language strings

All user-visible strings must use Joomla's language system:

- Admin strings: `admin/language/en-GB/com_livingword.ini` and `.sys.ini`
- Site strings: `site/language/en-GB/com_livingword.ini`
- Never hardcode English strings in PHP or template files

## Questions?

Open a [Discussion](../../discussions) — we're friendly and this is a ministry project.
We're glad you're here.
