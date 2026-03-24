# CWMLivingWord — Feature Improvement Project Plan
**Repository:** `Joomla-Bible-Study/CWMLivingWord`
**Component:** `com_livingword` (Joomla 5/6 Bible Reading Plan)
**Maintainers:** bcordis, tomfuller2

---

## Step 1 — Repository Infrastructure Setup

Before any feature work begins, set up the GitHub collaboration infrastructure that makes the project manageable.

### 1a. Enable GitHub Discussions
- Go to **Settings → General → Features** on the repo
- Check **Discussions**
- Create the following discussion categories:
  - 📣 **Announcements** — Release notes, roadmap updates (maintainer-only post)
  - 💡 **Feature Ideas** — Community suggestions and voting
  - 🙏 **Help & Support** — How-to questions from church admins
  - 🗺️ **Roadmap** — Long-form planning threads per milestone
  - 🔬 **Design Decisions** — Architecture discussions (schema changes, API choices)

### 1b. Create a GitHub Project (Board)
- Go to the **Joomla-Bible-Study org** → **Projects** → **New Project**
- Name: `CWMLivingWord Improvements`
- Type: **Board** (Kanban) with columns:
  - `Backlog` — All accepted issues not yet scheduled
  - `Milestone 1 – Engagement` — Scheduled for M1
  - `Milestone 2 – Social` — Scheduled for M2
  - `Milestone 3 – Content` — Scheduled for M3
  - `In Progress` — Actively being coded
  - `In Review` — PR open, awaiting review
  - `Done` — Merged to master

- Link the project to the `CWMLivingWord` repository
- Enable **Auto-add**: any issue opened in the repo auto-adds to Backlog

### 1c. Add .github Support Files
Create these files in the repository:

**Files to add:**
- `.github/ISSUE_TEMPLATE/bug_report.yml`
- `.github/ISSUE_TEMPLATE/feature_request.yml`
- `.github/ISSUE_TEMPLATE/church_admin_request.yml`
- `.github/PULL_REQUEST_TEMPLATE.md`
- `.github/DISCUSSION_TEMPLATE/feature_ideas.yml`
- `CONTRIBUTING.md`
- `CHANGELOG.md`
- `.github/CODEOWNERS`

### 1d. Configure Labels
Create these labels (delete defaults first):
```
type: bug          #d73a4a   Something isn't working
type: feature      #0075ca   New feature or enhancement
type: docs         #0075ca   Documentation changes
type: refactor     #e4e669   Code improvement, no behavior change
type: test         #e4e669   Test coverage additions
type: security     #b60205   Security-related fix
priority: high     #b60205   Blocking or high user impact
priority: medium   #fbca04   Standard priority
priority: low      #0e8a16   Nice to have
milestone: M1      #5319e7   Engagement & Progress Tracking
milestone: M2      #3e4b9e   Social & Accountability
milestone: M3      #1d76db   Content & Reading Experience
milestone: M4      #0052cc   Notifications & Admin
good first issue   #7057ff   Good for newcomers
needs: design      #e99695   Needs DB or API design discussion
needs: review      #f9d0c4   Awaiting maintainer review
```

---

## Step 2 — GitHub Milestones

Create these milestones in **Issues → Milestones**:

| Milestone | Title | Target | Description |
|-----------|-------|--------|-------------|
| M1 | Engagement & Progress Tracking | 3 months | Completion checkboxes, progress bar, streaks, catch-up UX |
| M2 | Social & Accountability | 6 months | Group plans, accountability partners, pastor dashboard |
| M3 | Content & Reading Experience | 9 months | Inline Bible text, audio playback, devotional content |
| M4 | Notifications & Delivery | 12 months | User-controlled email timing, unsubscribe tokens, weekly digest |

---

## Step 3 — Issue Creation Plan

Create the following issues (in order — earlier ones may be prerequisites):

---

### MILESTONE 1 — Engagement & Progress Tracking

#### Issue #1: Add per-day reading completion tracking
**Labels:** `type: feature`, `priority: high`, `milestone: M1`
**Discussion:** Open a Design Decisions thread: "Schema design for completion tracking"

The `#__livingword` table needs a completion mechanism. Options:
- **Option A:** Add a `completed_days` JSON/text column to `#__livingword` (simplest, works for 365-day plans)
- **Option B:** New table `#__livingword_progress` with `(userid, plan_name, day_number, completed_at)` (normalized, supports multiple plans per user)

Option B is recommended. Enables future multi-plan support.

**Schema addition:**
```sql
CREATE TABLE `#__livingword_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `plan_name` varchar(50) NOT NULL,
  `day_number` int(11) NOT NULL,
  `completed_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_plan_day` (`userid`, `plan_name`, `day_number`),
  KEY `idx_userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Frontend changes:**
- Add "Mark as read" button/checkbox on `cwmhome` view
- AJAX controller action: `MarkComplete` → inserts/deletes from progress table
- Show checkmark on calendar and list views for completed days

---

#### Issue #2: Progress indicator (% complete + day count)
**Labels:** `type: feature`, `priority: high`, `milestone: M1`
**Depends on:** Issue #1

On `cwmhome` and `cwmplanview`, show:
- "Day X of Y" based on current reading position
- "X% complete" calculated from completed days in progress table (Issue #1)
- Optional: visual progress bar (CSS only, no JS dependency)

---

#### Issue #3: Reading streak tracking
**Labels:** `type: feature`, `priority: high`, `milestone: M1`
**Depends on:** Issue #1

Calculate consecutive days with at least one completed reading. Store in `#__livingword` as:
```sql
ALTER TABLE `#__livingword`
  ADD `streak_current` int(11) NOT NULL DEFAULT 0,
  ADD `streak_best` int(11) NOT NULL DEFAULT 0,
  ADD `streak_last_date` date DEFAULT NULL;
```

Update on each `MarkComplete` action. Display on home view. Allow opt-out in user settings (some users find streaks anxiety-inducing — YouVersion learned this).

---

#### Issue #4: Catch-up / skip UX improvement
**Labels:** `type: feature`, `priority: high`, `milestone: M1`

The `dateoffset` field exists but is not user-facing. Add to the Settings view:
- "I'm behind — skip to today's actual date" button (resets offset to 0)
- "I'm ahead — stay on my current reading" (keeps offset)
- Manual day selector: "Jump to day ___"
- Show current position clearly: "You're reading Day 142 (your calendar is Jan 15 but you started Jan 1)"

---

#### Issue #5: Chapter-level completion within a day's reading
**Labels:** `type: feature`, `priority: medium`, `milestone: M1`
**Depends on:** Issue #1

Many plans have multi-part daily readings (e.g., "Genesis 1-3; Psalm 1; Matthew 1"). Allow marking individual passages complete within a day, not just the whole day. Progress table needs a `passage_index` column or a separate passage-completion table.

---

### MILESTONE 2 — Social & Accountability

#### Issue #6: Group reading plans (church campaigns)
**Labels:** `type: feature`, `priority: high`, `milestone: M2`, `needs: design`
**Discussion:** Open a Design Decisions thread: "Group plan schema and permission model"

New tables needed:
```sql
-- Groups (a church congregation or small group)
CREATE TABLE `#__livingword_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `plan_name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `created_by` int(11) NOT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Group members
CREATE TABLE `#__livingword_group_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `role` enum('admin','member') NOT NULL DEFAULT 'member',
  `joined_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_user` (`group_id`, `userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Frontend: group view showing member progress, admin can create/manage groups, members join via link or admin invite.

---

#### Issue #7: Accountability partner feature
**Labels:** `type: feature`, `priority: high`, `milestone: M2`
**Depends on:** Issue #1

Simpler than full groups. Add to `#__livingword`:
```sql
ALTER TABLE `#__livingword`
  ADD `accountability_partner_id` int(11) DEFAULT NULL,
  ADD `share_progress` tinyint(1) NOT NULL DEFAULT 0;
```

Weekly email to accountability partner showing: days completed, current streak, plan progress %. Partner can see (not modify) your reading progress. Settings UI to select partner from Joomla users.

---

#### Issue #8: Pastor / admin congregation progress dashboard
**Labels:** `type: feature`, `priority: medium`, `milestone: M2`
**Depends on:** Issue #1, Issue #6

New admin view `cwmstats` showing:
- Total subscribers vs active last 7 days
- Plan enrollment breakdown (which plans, how many)
- Average progress % across all users on a plan
- Users who haven't read in 7+ days (opt-in to flag)
- Group progress summaries (M2 Issue #6)

---

### MILESTONE 3 — Content & Reading Experience

#### Issue #9: Inline Bible text via API.Bible
**Labels:** `type: feature`, `priority: high`, `milestone: M3`, `needs: design`
**Discussion:** Open a Design Decisions thread: "Bible text API selection and licensing"

Options:
- **API.Bible** (free for non-commercial, 5000 req/day, 900+ translations) — best fit for CWM
- **ESV API** (free for low-volume non-commercial, ESV only)
- **Bible Gateway embed** (current approach — links out)

Implementation: cache API responses in a new `#__livingword_bible_cache` table keyed on `(version, book, chapter)` to avoid hammering the API daily. Display inline with a config toggle to fall back to BibleGateway links.

---

#### Issue #10: Audio Bible playback
**Labels:** `type: feature`, `priority: high`, `milestone: M3`

The schema has `audio` fields and config has `config_show_audio`. Verify what's actually wired vs what's placeholder. If placeholder:
- API.Bible provides audio for some versions (KJV, ASV)
- Alternative: link to Bible.is audio player for each passage (free, no API key)
- Add an HTML5 `<audio>` player to `cwmhome` when audio is available
- Add `audio_available` flag to plan details admin form

---

#### Issue #11: Devotional/reflection content per reading day
**Labels:** `type: feature`, `priority: medium`, `milestone: M3`

The `description` and `figures` fields exist on `#__livingword_plans_details` but appear unused in templates. Implement:
- Show `description` as a devotional note below the reading reference on `cwmhome`
- Show `figures` (key biblical figures) as a sidebar or tag list
- Admin form: make description a rich-text field (JEditor) not plain textarea
- Template: styled "Today's Reflection" section

---

#### Issue #12: Short-duration plan support (3–21 day plans)
**Labels:** `type: feature`, `priority: medium`, `milestone: M3`

Current plans all repeat annually. Add to `#__livingword_plans`:
```sql
ALTER TABLE `#__livingword_plans`
  ADD `duration_type` enum('annual','fixed','self_paced') NOT NULL DEFAULT 'annual',
  ADD `total_days` int(11) DEFAULT NULL;
```

`annual`: current behavior (365 days, repeats)
`fixed`: plan has N days, ends when complete (no repeat)
`self_paced`: user advances manually via "Mark complete and move on" rather than by date

---

### MILESTONE 4 — Notifications & Delivery

#### Issue #13: One-click email unsubscribe (CAN-SPAM compliance)
**Labels:** `type: feature`, `priority: high`, `milestone: M4`, `type: security`

**This is a legal compliance item — prioritize.** The task plugin sends daily emails with no unsubscribe mechanism. Required changes:
- Add `unsubscribe_token` (random UUID) to `#__livingword`
- Generate on user registration or first email send
- Include `?task=unsubscribe&token=XXX` link in every email footer
- New public controller action that validates token, sets `email=0`, shows confirmation page
- No login required for unsubscribe

---

#### Issue #14: User-controlled email delivery time preference
**Labels:** `type: feature`, `priority: medium`, `milestone: M4`

Add `email_hour` (0–23, user's local timezone) to `#__livingword`. Task plugin reads preferred hour and only emails users whose preferred hour matches the current run time. Requires the task to run hourly rather than once daily. Add timezone field to user settings.

---

#### Issue #15: Weekly progress digest email
**Labels:** `type: feature`, `priority: low`, `milestone: M4`
**Depends on:** Issue #1, Issue #13

Separate task plugin task (or a new task type on the existing plugin): sends a Sunday summary email to users who opt in. Content: readings completed this week, current streak, days ahead/behind pace, encouragement message. Opt-in setting in user preferences.

---

#### Issue #16: Plan import via CSV
**Labels:** `type: feature`, `priority: low`, `milestone: M4`

Admin utility to bulk-import reading plan details from CSV with columns:
`day_number, reading, audio, description, figures`

Add as a new admin controller action under Utilities. Validate format, preview first 5 rows before import, report success/errors.

---

## Step 4 — .github Files to Create

### File: `.github/ISSUE_TEMPLATE/bug_report.yml`
```yaml
name: Bug Report
description: Something isn't working correctly
labels: ["type: bug", "needs: review"]
body:
  - type: markdown
    attributes:
      value: "Thanks for taking the time to report a bug!"
  - type: input
    id: joomla-version
    attributes:
      label: Joomla version
      placeholder: "e.g. 6.0.1"
    validations:
      required: true
  - type: input
    id: php-version
    attributes:
      label: PHP version
      placeholder: "e.g. 8.3.4"
    validations:
      required: true
  - type: textarea
    id: description
    attributes:
      label: Describe the bug
      placeholder: What happened? What did you expect to happen?
    validations:
      required: true
  - type: textarea
    id: steps
    attributes:
      label: Steps to reproduce
      placeholder: "1. Go to...\n2. Click...\n3. See error"
    validations:
      required: true
  - type: textarea
    id: context
    attributes:
      label: Additional context
      placeholder: Screenshots, error logs, etc.
```

### File: `.github/ISSUE_TEMPLATE/feature_request.yml`
```yaml
name: Feature Request
description: Suggest a new feature or enhancement
labels: ["type: feature", "needs: review"]
body:
  - type: markdown
    attributes:
      value: "Have an idea? We'd love to hear it."
  - type: textarea
    id: problem
    attributes:
      label: What problem does this solve?
      placeholder: Describe the use case or problem you're trying to solve
    validations:
      required: true
  - type: textarea
    id: solution
    attributes:
      label: Proposed solution
      placeholder: Describe what you'd like to see
    validations:
      required: true
  - type: dropdown
    id: audience
    attributes:
      label: Who benefits most?
      options:
        - Site visitors / readers
        - Registered church members
        - Church staff / administrators
        - Site developers
    validations:
      required: true
  - type: textarea
    id: alternatives
    attributes:
      label: Alternatives considered
      placeholder: Any other approaches you considered?
```

### File: `.github/ISSUE_TEMPLATE/church_admin_request.yml`
```yaml
name: Church Admin Request
description: A request specific to managing a church reading program
labels: ["type: feature", "priority: medium"]
body:
  - type: markdown
    attributes:
      value: "This template is for church administrators running LivingWord for their congregation."
  - type: textarea
    id: context
    attributes:
      label: Describe your church's reading program
      placeholder: "How many members? What plans do you use? Any recurring challenges?"
    validations:
      required: true
  - type: textarea
    id: request
    attributes:
      label: What would help you?
      placeholder: Describe the feature or workflow improvement you need
    validations:
      required: true
```

### File: `.github/PULL_REQUEST_TEMPLATE.md`
```markdown
## Summary
<!-- What does this PR do? One sentence. -->

## Related issues
Closes #<!-- issue number -->

## Type of change
- [ ] Bug fix
- [ ] New feature
- [ ] Refactor / code improvement
- [ ] Documentation
- [ ] Test coverage

## Testing
<!-- How did you test this? What should reviewers check? -->

- [ ] `composer test` passes
- [ ] `composer lint` passes
- [ ] Tested on Joomla 5.x
- [ ] Tested on Joomla 6.x (if applicable)
- [ ] Database migration tested (if schema changed)

## Screenshots / demo
<!-- If UI changes, add before/after screenshots -->

## Checklist
- [ ] Language strings added to `en-GB` files
- [ ] SQL update script added if schema changed (`admin/sql/updates/`)
- [ ] ACL permissions updated in `access.xml` if new protected action
- [ ] README updated if behavior changed
```

### File: `CONTRIBUTING.md`
```markdown
# Contributing to CWMLivingWord

Thank you for helping improve the CWM LivingWord Bible reading component!

## Ways to contribute
- **Bug reports** — Use the Bug Report issue template
- **Feature ideas** — Start a discussion in [GitHub Discussions → Feature Ideas](../../discussions)
- **Code** — See development setup in README.md
- **Documentation** — Improvements to README, inline docs, or wiki

## Development workflow
1. Fork the repository
2. Create a branch: `git checkout -b feature/your-feature-name`
3. Follow the code style (PSR-12, run `composer lint:fix`)
4. Write/update tests where applicable
5. Run `composer check` before pushing
6. Open a pull request against `master` using the PR template

## Branch naming
- `feature/` — new functionality
- `fix/` — bug fixes
- `refactor/` — code improvement
- `docs/` — documentation only

## Commit messages
Use conventional commits format:
```
feat: add completion checkbox to home view
fix: correct day calculation when dateoffset is negative
refactor: extract email sending to dedicated service class
docs: update README with new API.Bible configuration
```

## Questions?
Open a discussion — we're friendly and this is a ministry project. We're glad you're here.
```

### File: `.github/CODEOWNERS`
```
# Default reviewers for all files
* @bcordis @tomfuller2

# Database migrations get extra scrutiny
admin/sql/ @bcordis

# Task plugin (sends emails to real users)
plg_task_livingword/ @bcordis @tomfuller2
```

### File: `CHANGELOG.md`
```markdown
# Changelog

All notable changes to CWMLivingWord are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]
### Planned (Milestone 1)
- Reading completion checkboxes per day
- Progress indicator (day count + percentage)
- Reading streak tracking
- Catch-up / skip UX improvements
- One-click email unsubscribe (CAN-SPAM compliance)

## [5.0.0] — 2026
### Added
- Joomla 5/6 migration with namespaced MVC and PSR-4 autoloading
- PHP 8.3+ compatibility
- Joomla Task Scheduler plugin for daily email notifications
- Parallel Bible version comparison support
- Admin database utilities (optimize, check, repair, backup)
- GitHub Actions CI with CodeQL security scanning

### Changed
- Complete rewrite from Joomla 3.x architecture
- Database tables converted from MyISAM to InnoDB

### Removed
- All legacy Joomla 3.x JFactory / JText / JRoute patterns
```

---

## Step 5 — Discussion Threads to Seed

Once Discussions is enabled, open these pinned threads:

### Thread 1 (Roadmap category): "CWMLivingWord Improvement Roadmap — 2026"
Paste a summary of the 4 milestones. Pin it. This gives contributors context.

### Thread 2 (Design Decisions): "Schema design for reading completion tracking (Issue #1)"
Lay out Option A vs Option B from Issue #1 above. Ask for community input before coding begins.

### Thread 3 (Design Decisions): "Bible text API selection — API.Bible vs ESV API vs BibleGateway links"
Present the options from Issue #9. This is a significant architectural decision that affects licensing and caching strategy.

### Thread 4 (Design Decisions): "Group plan permission model — how should church admins assign plans?"
Questions to resolve: Can members self-join? Are groups invitation-only? Can a group admin see individual member readings? Does joining a group override personal plan selection?

### Thread 5 (Feature Ideas): "What features would help your church most?"
An open invitation thread for church admins using LivingWord to share their real-world needs before the team builds anything.

---

## Step 6 — First PR / Quick Wins

Before starting M1 feature work, open one PR that adds all the .github infrastructure files from Step 4. This:
- Establishes the contributing workflow
- Makes future issue triaging faster
- Shows the project is actively maintained

**Branch name:** `chore/github-project-infrastructure`

Commit the following in one PR:
- `.github/ISSUE_TEMPLATE/bug_report.yml`
- `.github/ISSUE_TEMPLATE/feature_request.yml`
- `.github/ISSUE_TEMPLATE/church_admin_request.yml`
- `.github/PULL_REQUEST_TEMPLATE.md`
- `.github/CODEOWNERS`
- `CONTRIBUTING.md`
- `CHANGELOG.md`

---

## Summary Checklist

### Repository setup (do first)
- [ ] Enable Discussions in repo Settings
- [ ] Create GitHub Project board with correct columns
- [ ] Link project to repository and enable auto-add
- [ ] Create labels (delete defaults, add custom set)
- [ ] Create 4 milestones (M1–M4) with target dates
- [ ] Open PR: `chore/github-project-infrastructure`
- [ ] Seed the 5 discussion threads

### Issue creation
- [ ] Issue #1: Completion tracking schema + `MarkComplete` controller action
- [ ] Issue #2: Progress indicator (Day X of Y, % bar)
- [ ] Issue #3: Streak tracking
- [ ] Issue #4: Catch-up / skip UX
- [ ] Issue #5: Chapter-level completion
- [ ] Issue #6: Group reading plans (schema design first)
- [ ] Issue #7: Accountability partner
- [ ] Issue #8: Admin congregation dashboard
- [ ] Issue #9: Inline Bible text via API.Bible
- [ ] Issue #10: Audio playback
- [ ] Issue #11: Devotional content display
- [ ] Issue #12: Short-duration plan support
- [ ] Issue #13: CAN-SPAM unsubscribe ← do this early regardless of milestone
- [ ] Issue #14: User email time preference
- [ ] Issue #15: Weekly digest email
- [ ] Issue #16: CSV plan import
