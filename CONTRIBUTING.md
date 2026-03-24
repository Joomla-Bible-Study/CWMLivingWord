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

1. Fork the repository
2. Create a branch from `master`:
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. Make your changes, following the code style guidelines below
4. Write or update tests if applicable
5. Run the full check before pushing:
   ```bash
   composer check    # syntax + lint + tests
   ```
6. Open a pull request against `master` using the PR template

## Branch naming conventions

| Prefix | Use for |
|--------|---------|
| `feature/` | New functionality |
| `fix/` | Bug fixes |
| `refactor/` | Code improvement, no behavior change |
| `docs/` | Documentation only |
| `chore/` | Tooling, CI, infrastructure |
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
