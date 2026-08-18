#!/usr/bin/env php
<?php

/**
 * LivingWord — seed reader scenarios into a development or test install.
 *
 * The install SQL seeds the *catalog* — plans, their days, and the resource
 * links. Nothing seeds *state*, so every reader on a fresh install is day 1
 * with a zero streak, no group, no partner and no notes. That leaves the
 * interesting paths — streaks, mid-plan day maths, the timezone x email-hour
 * pair behind the daily-mail routine, group membership, partner digests —
 * reachable only by clicking a site into position by hand.
 *
 * This script puts a fixed cast of readers into those positions, and is safe
 * to re-run: every write is an upsert or a scoped delete-then-insert, keyed on
 * the seed usernames below.
 *
 * Usage:
 *   php build/seed_scenarios.php --install=j6-dev
 *   php build/seed_scenarios.php --install=j6-test --reset
 *   php build/seed_scenarios.php --list
 *
 * Every date is computed from *today*, never written as a literal. A fixture
 * that says "day 47 with a live 12-day streak" has to still mean that next
 * month, and a hardcoded start date rots silently into a different scenario.
 */

use CWM\BuildTools\Dev\InstallConfig;
use CWM\BuildTools\Dev\PropertiesReader;
use CWM\BuildTools\Dev\TestSite;

const ROOT = __DIR__ . '/..';

require_once ROOT . '/libraries/vendor/autoload.php';

/**
 * Usernames are prefixed so the cast is identifiable at a glance in the user
 * manager, and so --reset can find exactly what it created. The e2e accounts
 * (lw-e2e-member, lw-e2e-partner) are deliberately NOT touched: global-setup.js
 * owns those, and two things managing one account is how fixtures start
 * fighting each other.
 */
const PREFIX = 'lw-seed-';

/**
 * Group keys. The invite token for each is derived, not literal, so it is both
 * deterministic — a re-run matches the existing row rather than duplicating it —
 * and exactly the 64 characters the column holds.
 */
const GROUP_KEYS = ['morning', 'elders', 'empty'];

/**
 * The cast.
 *
 * `day` is the reading day the reader should be sitting on today; the start
 * date is computed backwards from it. `progress_through` is the last day with
 * completion rows, which is what separates a reader who is up to date from one
 * who stopped a week ago on the same plan day.
 *
 * Streak fields are stored exactly as the application would leave them.
 * CwmprogressHelper::updateStreak() only ever resets streak_current to 1 on the
 * *next* completion, so a reader who lapsed keeps their old streak_current
 * until they read again — 'lapsed' below reproduces that rather than a tidier
 * zero, because the stale value is what a real site holds.
 */
const READERS = [
    [
        'username'  => PREFIX . 'newcomer',
        'name'      => 'Seed Newcomer',
        'plan'      => 'comp',
        'day'       => 1,
        'progress_through' => 0,
        'streak'    => ['current' => 0, 'best' => 0, 'last' => null],
        'email'     => 1,
        'timezone'  => 'America/Chicago',
        'hour'      => 6,
        'note'      => 'Just subscribed. Day 1, nothing completed.',
    ],
    [
        'username'  => PREFIX . 'midplan',
        'name'      => 'Seed Midplan Reader',
        'plan'      => 'comp',
        'day'       => 47,
        'progress_through' => 46,
        'streak'    => ['current' => 12, 'best' => 12, 'last' => '-1 day'],
        'email'     => 1,
        'timezone'  => 'America/Chicago',
        'hour'      => 6,
        'share'     => 1,
        'note'      => 'Up to date, live 12-day streak, today still unread.',
    ],
    [
        'username'  => PREFIX . 'lapsed',
        'name'      => 'Seed Lapsed Reader',
        'plan'      => 'comp',
        'day'       => 47,
        'progress_through' => 41,
        'streak'    => ['current' => 31, 'best' => 31, 'last' => '-6 days'],
        'email'     => 1,
        'timezone'  => 'America/Chicago',
        'hour'      => 6,
        'note'      => 'Same plan day as midplan, but stopped six days ago.',
    ],
    [
        'username'  => PREFIX . 'finisher',
        'name'      => 'Seed Finisher',
        'plan'      => 'comp',
        'day'       => 365,
        'progress_through' => 365,
        'streak'    => ['current' => 365, 'best' => 365, 'last' => 'today'],
        'email'     => 1,
        'timezone'  => 'America/Chicago',
        'hour'      => 6,
        'note'      => 'Whole plan complete. Already read today.',
    ],
    [
        'username'  => PREFIX . 'optout',
        'name'      => 'Seed Opt-Out',
        'plan'      => 'comp',
        'day'       => 20,
        'progress_through' => 19,
        'streak'    => ['current' => 5, 'best' => 9, 'last' => '-1 day'],
        'email'     => 0,
        'timezone'  => 'America/Chicago',
        'hour'      => 6,
        'note'      => 'email = 0. Must never appear in a daily-mail run.',
    ],
    [
        'username'  => PREFIX . 'sydney',
        'name'      => 'Seed Sydney Reader',
        'plan'      => 'comp',
        'day'       => 15,
        'progress_through' => 14,
        'streak'    => ['current' => 3, 'best' => 3, 'last' => '-1 day'],
        'email'     => 1,
        'timezone'  => 'Australia/Sydney',
        'hour'      => 6,
        'note'      => 'Ahead of UTC far enough to sit on the next calendar day.',
    ],
    [
        'username'  => PREFIX . 'utc',
        'name'      => 'Seed UTC Reader',
        'plan'      => 'comp',
        'day'       => 10,
        'progress_through' => 9,
        'streak'    => ['current' => 2, 'best' => 2, 'last' => '-1 day'],
        'email'     => 1,
        'timezone'  => 'UTC',
        'hour'      => 0,
        'note'      => 'Midnight edge — hour 0 is where an off-by-one shows.',
    ],
    [
        'username'  => PREFIX . 'london',
        'name'      => 'Seed London Reader',
        'plan'      => 'comp',
        'day'       => 8,
        'progress_through' => 7,
        'streak'    => ['current' => 1, 'best' => 4, 'last' => '-1 day'],
        'email'     => 1,
        'timezone'  => 'Europe/London',
        'hour'      => 23,
        'note'      => 'Hour 23 under a zone that shifts between GMT and BST.',
    ],
    [
        'username'  => PREFIX . 'nozone',
        'name'      => 'Seed No-Zone Reader',
        'plan'      => 'comp',
        'day'       => 5,
        'progress_through' => 4,
        'streak'    => ['current' => 1, 'best' => 1, 'last' => '-1 day'],
        'email'     => 1,
        'timezone'  => '',
        'hour'      => 6,
        'note'      => "Empty timezone — exercises the app's offset fallback.",
    ],
    [
        'username'  => PREFIX . 'blocked',
        'name'      => 'Seed Blocked Reader',
        'plan'      => 'comp',
        'day'       => 12,
        'progress_through' => 11,
        'streak'    => ['current' => 2, 'best' => 2, 'last' => '-1 day'],
        'email'     => 1,
        'timezone'  => 'America/Chicago',
        'hour'      => 6,
        'blocked'   => true,
        'note'      => 'email = 1 but blocked in Joomla. The routine must skip them.',
    ],
    [
        'username'  => PREFIX . 'leader',
        'name'      => 'Seed Group Leader',
        'plan'      => 'ttb',
        'day'       => 30,
        'progress_through' => 29,
        'streak'    => ['current' => 8, 'best' => 20, 'last' => '-1 day'],
        'email'     => 1,
        'timezone'  => 'America/Chicago',
        'hour'      => 7,
        'share'     => 1,
        'note'      => 'Leads three groups, and is on a different plan to its members.',
    ],
    [
        'username'  => PREFIX . 'partner',
        'name'      => 'Seed Partner',
        'plan'      => 'comp',
        'day'       => 47,
        'progress_through' => 45,
        'streak'    => ['current' => 9, 'best' => 15, 'last' => '-1 day'],
        'email'     => 1,
        'timezone'  => 'America/Chicago',
        'hour'      => 6,
        'share'     => 1,
        'note'      => 'Mutual partner of midplan, sharing progress.',
    ],
];

/**
 * Pairings.
 *
 * The digest only mails a mutual pairing where the partner shares progress, so
 * the cast needs one pairing that qualifies and several that must not:
 * a one-way pointer, a mutual pairing with sharing off, and a dangling id.
 */
const PARTNERS = [
    // Mutual, both sharing — the only pair a digest should act on.
    [PREFIX . 'midplan',  PREFIX . 'partner',  'mutual'],
    // One-way: finisher points at lapsed, lapsed points back at nobody.
    [PREFIX . 'finisher', PREFIX . 'lapsed',   'one-way'],
    // Mutual but sharing is off on one side.
    [PREFIX . 'sydney',   PREFIX . 'utc',      'mutual-no-share'],
    // Points at a user id that does not exist.
    [PREFIX . 'london',   null,                'dangling'],
];

const GROUPS = [
    [
        'key'       => 'morning',
        'name'      => 'Seed Morning Readers',
        'plan'      => 'comp',
        'join_mode' => 'open',
        'leader'    => PREFIX . 'leader',
        'members'   => [PREFIX . 'midplan', PREFIX . 'lapsed', PREFIX . 'newcomer'],
        'note'      => 'Open join, three members plus the leader.',
    ],
    [
        'key'       => 'elders',
        'name'      => 'Seed Elders Study',
        'plan'      => 'ttb',
        'join_mode' => 'invite',
        'leader'    => PREFIX . 'leader',
        'members'   => [PREFIX . 'partner'],
        'note'      => 'Invite-only with a live token for the invite landing page.',
    ],
    [
        'key'       => 'empty',
        'name'      => 'Seed Empty Cohort',
        'plan'      => 'comp',
        'join_mode' => 'open',
        'leader'    => PREFIX . 'leader',
        'members'   => [],
        'note'      => 'Leader only — the singular/plural boundary on member counts.',
    ],
];

/** day => text. Seeded for midplan and newcomer. */
const NOTES = [
    PREFIX . 'midplan'  => [40, 41, 42, 45, 46, 47],
    PREFIX . 'newcomer' => [1, 2, 3],
];

exit(main($argv));

/**
 * @param   array<int, string>  $argv  Raw CLI arguments.
 *
 * @return  int  Process exit code.
 */
function main(array $argv): int
{
    $args = parseArgs($argv);

    if (isset($args['help'])) {
        usage();

        return 0;
    }

    if (isset($args['list'])) {
        listCast();

        return 0;
    }

    try {
        $install = resolveInstall($args['install'] ?? null);

        // The install supplies the path; the credentials and the prefix both
        // come from the site's own configuration.php. Previously the prefix was
        // read from there and the credentials from build.properties, so when
        // the two disagreed this connected to one database and wrote with the
        // other's prefix.
        $site   = TestSite::fromInstall($install);
        $prefix = $site->prefix();
        $pdo    = $site->db();
    } catch (\RuntimeException $e) {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");

        return 1;
    }

    printf("Install: %s (%s)\n", $install->id, $install->path ?: 'no path');
    printf("Database: %s, prefix %s\n\n", $site->database(), $prefix);

    if (isset($args['reset'])) {
        resetSeed($pdo, $prefix, $install);
        echo "\nReset complete. Nothing seeded.\n";

        return 0;
    }

    if ($install->path === '') {
        fwrite(
            STDERR,
            "Error: install '{$install->id}' declares no path, and creating Joomla accounts needs\n"
            . "its cli/joomla.php. Add builder.{$install->id}.path to build.properties.\n"
        );

        return 1;
    }

    $userIds = ensureAccounts($pdo, $prefix, $install);
    $planIds = resolvePlans($pdo, $prefix);

    seedReaders($pdo, $prefix, $userIds, $planIds);
    seedPartners($pdo, $prefix, $userIds);
    seedGroups($pdo, $prefix, $userIds, $planIds);
    seedNotes($pdo, $prefix, $userIds, $planIds);

    echo "\nDone. Re-run any time; every write is keyed on the seed usernames.\n";

    return 0;
}

/**
 * @param   array<int, string>  $argv
 *
 * @return  array<string, string|bool>
 */
function parseArgs(array $argv): array
{
    $out = [];

    foreach (\array_slice($argv, 1) as $arg) {
        if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $arg, $m) === 1) {
            $out[$m[1]] = $m[2] ?? true;
        }
    }

    return $out;
}

function usage(): void
{
    echo <<<TXT
    Seed LivingWord reader scenarios into a development or test install.

      --install=<id>   Install id from build.properties (e.g. j6-dev, j6-test).
                       Defaults to the only install when there is just one.
      --reset          Remove every seeded row and Joomla account, then stop.
      --list           Print the cast and exit without touching a database.
      --help           This text.

    TXT;
}

function listCast(): void
{
    echo "Readers\n";

    foreach (READERS as $r) {
        printf(
            "  %-22s plan %-6s day %-4d %s\n",
            $r['username'],
            $r['plan'],
            $r['day'],
            $r['note']
        );
    }

    echo "\nPartner pairings\n";

    foreach (PARTNERS as [$a, $b, $kind]) {
        printf("  %-22s -> %-22s %s\n", $a, $b ?? '(missing user id)', $kind);
    }

    echo "\nGroups\n";

    foreach (GROUPS as $g) {
        printf("  %-22s %-7s %d member(s)  %s\n", $g['name'], $g['join_mode'], \count($g['members']), $g['note']);
    }
}

/**
 * Resolve the target install from build.properties.
 *
 * @param   string|bool|null  $id  Value of --install, if given.
 */
function resolveInstall(string|bool|null $id): InstallConfig
{
    $reader   = new PropertiesReader(ROOT . '/build.properties');
    $installs = $reader->installs();

    if (\is_string($id)) {
        foreach ($installs as $install) {
            if ($install->id === $id) {
                return $install;
            }
        }

        $known = implode(', ', array_map(static fn ($i) => $i->id, $installs));

        throw new \RuntimeException("No install '{$id}' in build.properties. Known: {$known}");
    }

    if (\count($installs) === 1) {
        return $installs[0];
    }

    $known = implode(', ', array_map(static fn ($i) => $i->id, $installs));

    throw new \RuntimeException("Pass --install=<id>. Known: {$known}");
}


/**
 * Create the Joomla accounts, then return username => user id.
 *
 * Accounts go through the install's own CLI rather than a direct INSERT so the
 * password is hashed the way Joomla expects and the usergroup mapping is
 * written — a hand-inserted #__users row cannot log in.
 *
 * @return  array<string, int>
 */
function ensureAccounts(\PDO $pdo, string $prefix, InstallConfig $install): array
{
    $cli = $install->path . '/cli/joomla.php';

    if (!is_file($cli)) {
        throw new \RuntimeException("No cli/joomla.php under {$install->path}.");
    }

    echo "Accounts\n";

    foreach (READERS as $reader) {
        $existing = $pdo->prepare("SELECT id FROM `{$prefix}users` WHERE username = ?");
        $existing->execute([$reader['username']]);

        if ($existing->fetchColumn() !== false) {
            printf("  = %s\n", $reader['username']);
            continue;
        }

        $out = shellJoomla($cli, [
            'user:add',
            '--username=' . $reader['username'],
            '--name=' . $reader['name'],
            '--password=' . seedPassword($reader['username']),
            '--email=' . $reader['username'] . '@example.com',
            '--usergroup=Registered',
        ]);

        printf("  %s %s\n", preg_match('/created|already exists/i', $out) === 1 ? '+' : '!', $reader['username']);
    }

    // Blocked is a state the CLI cannot set, and the daily-mail routine joins on
    // u.block = 0, so a blocked subscriber is one of the cases worth holding.
    $ids = [];

    foreach (READERS as $reader) {
        $stmt = $pdo->prepare("SELECT id FROM `{$prefix}users` WHERE username = ?");
        $stmt->execute([$reader['username']]);
        $id = $stmt->fetchColumn();

        if ($id === false) {
            throw new \RuntimeException("Account {$reader['username']} was not created; cannot continue.");
        }

        $ids[$reader['username']] = (int) $id;

        $block = $pdo->prepare("UPDATE `{$prefix}users` SET block = ? WHERE id = ?");
        $block->execute([!empty($reader['blocked']) ? 1 : 0, (int) $id]);
    }

    return $ids;
}

/**
 * A per-account password. Derived, not random, so re-running prints the same
 * credentials and a tester can note them once.
 */
function seedPassword(string $username): string
{
    return 'Seed!' . substr(hash('sha256', $username), 0, 12);
}

/**
 * @param   array<int, string>  $args
 */
function shellJoomla(string $cli, array $args): string
{
    $cmd = array_merge(['php', $cli], $args);
    $esc = implode(' ', array_map('escapeshellarg', $cmd));

    return (string) shell_exec($esc . ' 2>&1');
}

/**
 * @return  array<string, int>  Plan alias => id.
 */
function resolvePlans(\PDO $pdo, string $prefix): array
{
    $rows = $pdo->query("SELECT alias, id FROM `{$prefix}livingword_plans`")->fetchAll(\PDO::FETCH_KEY_PAIR);

    if (!$rows) {
        throw new \RuntimeException('No plans found — the component install SQL has not run on this site.');
    }

    return array_map('intval', $rows);
}

/**
 * The number of days a plan actually holds, which is the row count in
 * plans_details rather than plans.total_days — every catalog plan leaves that
 * column NULL.
 */
function planDays(\PDO $pdo, string $prefix, int $planId): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}livingword_plans_details` WHERE plan_id = ?");
    $stmt->execute([$planId]);

    return (int) $stmt->fetchColumn();
}

/**
 * @param   array<string, int>  $userIds
 * @param   array<string, int>  $planIds
 */
function seedReaders(\PDO $pdo, string $prefix, array $userIds, array $planIds): void
{
    echo "\nReaders\n";

    $shortfall = 0;
    $narrowKey = hasNarrowProgressKey($pdo, $prefix);

    foreach (READERS as $reader) {
        $userId = $userIds[$reader['username']];
        $planId = $planIds[$reader['plan']] ?? 0;

        if ($planId === 0) {
            printf("  ! %s — no plan aliased '%s'\n", $reader['username'], $reader['plan']);
            continue;
        }

        $totalDays = planDays($pdo, $prefix, $planId);
        $day       = min($reader['day'], max($totalDays, 1));

        // Every catalog plan is duration_type 'annual', where the current day is
        // (today - start_date) + 1. Land on the wanted day by walking back.
        $startDate = (new \DateTimeImmutable('today'))
            ->modify('-' . ($day - 1) . ' days')
            ->format('Y-m-d');

        $streak = $reader['streak'];
        $last   = $streak['last'] === null
            ? null
            : (new \DateTimeImmutable('today'))->modify($streak['last'])->format('Y-m-d');

        $sql = "INSERT INTO `{$prefix}livingword_users`
                    (user_id, plan_id, bible_version, audio_version, email,
                     unsubscribe_token, action_token, plan_view, start_date, date_offset,
                     streak_current, streak_best, streak_last_date, email_hour, timezone,
                     accountability_partner_id, share_progress)
                VALUES (?, ?, 'kjv', 'kjv', ?, ?, ?, 0, ?, 0, ?, ?, ?, ?, ?, NULL, ?)
                ON DUPLICATE KEY UPDATE
                     plan_id = VALUES(plan_id),
                     email = VALUES(email),
                     unsubscribe_token = VALUES(unsubscribe_token),
                     action_token = VALUES(action_token),
                     start_date = VALUES(start_date),
                     streak_current = VALUES(streak_current),
                     streak_best = VALUES(streak_best),
                     streak_last_date = VALUES(streak_last_date),
                     email_hour = VALUES(email_hour),
                     timezone = VALUES(timezone),
                     share_progress = VALUES(share_progress)";

        $pdo->prepare($sql)->execute([
            $userId,
            $planId,
            $reader['email'],
            token('unsub', $reader['username']),
            token('action', $reader['username']),
            $startDate,
            $streak['current'],
            $streak['best'],
            $last,
            $reader['hour'],
            $reader['timezone'],
            $reader['share'] ?? 0,
        ]);

        $dropped = seedProgress($pdo, $prefix, $userId, $planId, (int) $reader['progress_through']);

        if ($dropped > 0) {
            $shortfall += $dropped;
        }

        printf(
            "  %-22s plan %-6s day %-4d progress->%-4d streak %d/%d  %s\n",
            $reader['username'],
            $reader['plan'],
            $day,
            $reader['progress_through'],
            $streak['current'],
            $streak['best'],
            $reader['timezone'] === '' ? '(no zone)' : $reader['timezone']
        );
    }

    if ($shortfall > 0) {
        printf(
            "\n  ! %d passage row(s) were rejected by the database.\n",
            $shortfall
        );

        if ($narrowKey) {
            echo "    This install still has the pre-#7 unique key on livingword_progress\n"
               . "    (user_id, plan_id, day) with no passage_index, so it can hold only one\n"
               . "    passage per day. install.mysql.utf8.sql widened that key when per-passage\n"
               . "    completion landed, but no update SQL was ever written, so upgraded sites\n"
               . "    never got it. CwmprogressHelper::markPassageComplete() swallows the same\n"
               . "    violation at runtime, which is why the site looks fine.\n";
        }
    }
}

/**
 * Completion rows for days 1..$through.
 *
 * markComplete() writes one row per passage, and a day's passages are its
 * reading split on ';'. Matching that matters: getCompletedPassageCounts()
 * compares stored rows against the split, so a single row per day would render
 * every multi-passage day as partially complete.
 */
function seedProgress(\PDO $pdo, string $prefix, int $userId, int $planId, int $through): int
{
    $pdo->prepare("DELETE FROM `{$prefix}livingword_progress` WHERE user_id = ? AND plan_id = ?")
        ->execute([$userId, $planId]);

    if ($through < 1) {
        return 0;
    }

    $readings = $pdo->prepare(
        "SELECT reading FROM `{$prefix}livingword_plans_details`
          WHERE plan_id = ? ORDER BY ordering ASC, id ASC LIMIT ?"
    );
    $readings->bindValue(1, $planId, \PDO::PARAM_INT);
    $readings->bindValue(2, $through, \PDO::PARAM_INT);
    $readings->execute();

    // IGNORE rather than plain INSERT because an upgraded site can still carry
    // the pre-#7 unique key on (user_id, plan_id, day), which admits only one
    // passage per day. Dropped rows are counted and reported by the caller —
    // silently degrading is how the application itself hides this drift.
    $insert = $pdo->prepare(
        "INSERT IGNORE INTO `{$prefix}livingword_progress` (user_id, plan_id, day, passage_index, completed_at)
         VALUES (?, ?, ?, ?, ?)"
    );

    $day     = 0;
    $wanted  = 0;
    $written = 0;

    foreach ($readings->fetchAll(\PDO::FETCH_COLUMN) as $reading) {
        $day++;

        // Completions are dated as if the reader worked forward one day at a
        // time and finished $through days ago, so completed_at ordering is
        // believable to anything that reads it.
        $completedAt = (new \DateTimeImmutable('today'))
            ->modify('-' . ($through - $day) . ' days')
            ->format('Y-m-d H:i:s');

        $passages = array_values(array_filter(array_map('trim', explode(';', (string) $reading))));
        $count    = max(\count($passages), 1);

        for ($i = 0; $i < $count; $i++) {
            $insert->execute([$userId, $planId, $day, $i, $completedAt]);
            $wanted++;
            $written += $insert->rowCount();
        }
    }

    return $wanted - $written;
}

/**
 * Whether this install still carries the pre-#7 unique key on the progress
 * table — UNIQUE (user_id, plan_id, day), with no passage_index.
 *
 * install.mysql.utf8.sql widened that key to include passage_index when
 * per-passage completion landed, but no update SQL was ever written for it, so
 * a site upgraded across that commit keeps the narrow key and can hold only one
 * passage per day. CwmprogressHelper::markPassageComplete() swallows the
 * resulting constraint violation, which is why nobody notices.
 */
function hasNarrowProgressKey(\PDO $pdo, string $prefix): bool
{
    $rows = $pdo->query("SHOW INDEX FROM `{$prefix}livingword_progress`")->fetchAll(\PDO::FETCH_ASSOC);

    $unique = [];

    foreach ($rows as $row) {
        if ((int) $row['Non_unique'] === 0 && $row['Key_name'] !== 'PRIMARY') {
            $unique[$row['Key_name']][] = $row['Column_name'];
        }
    }

    foreach ($unique as $columns) {
        if (\in_array('passage_index', $columns, true)) {
            return false;
        }
    }

    return $unique !== [];
}

/**
 * @param   array<string, int>  $userIds
 */
function seedPartners(\PDO $pdo, string $prefix, array $userIds): void
{
    echo "\nPartners\n";

    // A dangling id has to be one no user holds, so take it past the top of the
    // table rather than picking a number and hoping.
    $dangling = ((int) $pdo->query("SELECT COALESCE(MAX(id), 0) FROM `{$prefix}users`")->fetchColumn()) + 5000;

    $set = $pdo->prepare(
        "UPDATE `{$prefix}livingword_users` SET accountability_partner_id = ?, share_progress = ? WHERE user_id = ?"
    );

    foreach (PARTNERS as [$fromName, $toName, $kind]) {
        $from = $userIds[$fromName];
        $to   = $toName === null ? $dangling : $userIds[$toName];

        match ($kind) {
            // Both point at each other and both share.
            'mutual' => (static function () use ($set, $from, $to) {
                $set->execute([$to, 1, $from]);
                $set->execute([$from, 1, $to]);
            })(),
            // From points at to; to is left pointing wherever it already did.
            'one-way' => $set->execute([$to, 1, $from]),
            // Mutual, but the far side withholds progress.
            'mutual-no-share' => (static function () use ($set, $from, $to) {
                $set->execute([$to, 1, $from]);
                $set->execute([$from, 0, $to]);
            })(),
            'dangling' => $set->execute([$dangling, 1, $from]),
        };

        printf("  %-22s -> %-22s %s\n", $fromName, $toName ?? "(id {$dangling})", $kind);
    }
}

/**
 * @param   array<string, int>  $userIds
 * @param   array<string, int>  $planIds
 */
function seedGroups(\PDO $pdo, string $prefix, array $userIds, array $planIds): void
{
    echo "\nGroups\n";

    foreach (GROUPS as $group) {
        $token  = groupToken($group['key']);
        $leader = $userIds[$group['leader']];
        $planId = $planIds[$group['plan']] ?? 0;

        // Groups start a fortnight ago so member progress sits inside the window
        // rather than on its first day.
        $startDate = (new \DateTimeImmutable('today'))->modify('-14 days')->format('Y-m-d');

        $pdo->prepare(
            "INSERT INTO `{$prefix}livingword_groups`
                 (name, description, plan_id, start_date, invite_token, join_mode, created_by, published)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE
                 name = VALUES(name), description = VALUES(description), plan_id = VALUES(plan_id),
                 start_date = VALUES(start_date), join_mode = VALUES(join_mode), created_by = VALUES(created_by),
                 published = 1"
        )->execute([$group['name'], $group['note'], $planId, $startDate, $token, $group['join_mode'], $leader]);

        $idStmt = $pdo->prepare("SELECT id FROM `{$prefix}livingword_groups` WHERE invite_token = ?");
        $idStmt->execute([$token]);
        $groupId = (int) $idStmt->fetchColumn();

        $pdo->prepare("DELETE FROM `{$prefix}livingword_group_members` WHERE group_id = ?")->execute([$groupId]);

        $add = $pdo->prepare(
            "INSERT INTO `{$prefix}livingword_group_members` (group_id, user_id, role, joined_at) VALUES (?, ?, ?, ?)"
        );

        $add->execute([$groupId, $leader, 'leader', $startDate . ' 09:00:00']);

        foreach ($group['members'] as $index => $memberName) {
            $joined = (new \DateTimeImmutable($startDate))->modify('+' . ($index + 1) . ' days');
            $add->execute([$groupId, $userIds[$memberName], 'member', $joined->format('Y-m-d H:i:s')]);
        }

        printf(
            "  %-24s %-7s id %-4d %d member(s) + leader   token %s\n",
            $group['name'],
            $group['join_mode'],
            $groupId,
            \count($group['members']),
            substr($token, -12)
        );
    }
}

/**
 * @param   array<string, int>  $userIds
 * @param   array<string, int>  $planIds
 */
function seedNotes(\PDO $pdo, string $prefix, array $userIds, array $planIds): void
{
    echo "\nNotes\n";

    foreach (NOTES as $username => $days) {
        $userId = $userIds[$username];
        $reader = null;

        foreach (READERS as $candidate) {
            if ($candidate['username'] === $username) {
                $reader = $candidate;

                break;
            }
        }

        $planId = $planIds[$reader['plan']];

        $pdo->prepare("DELETE FROM `{$prefix}livingword_notes` WHERE user_id = ?")->execute([$userId]);

        $insert = $pdo->prepare(
            "INSERT INTO `{$prefix}livingword_notes` (user_id, plan_id, day, note_text) VALUES (?, ?, ?, ?)"
        );

        foreach ($days as $day) {
            $insert->execute([
                $userId,
                $planId,
                $day,
                "Seeded reflection for day {$day}. Written by build/seed_scenarios.php.",
            ]);
        }

        printf("  %-22s %d note(s) on days %s\n", $username, \count($days), implode(', ', $days));
    }
}

/**
 * Deterministic 64-char tokens, so a re-run does not invalidate a URL a tester
 * already has open. Real tokens are random; these only have to be unique and
 * stable within a seeded site.
 */
function token(string $kind, string $username): string
{
    return substr(hash('sha256', 'lw-seed:' . $kind . ':' . $username), 0, 64);
}

/**
 * The invite token for a seeded group.
 */
function groupToken(string $key): string
{
    return token('group', $key);
}

/**
 * Remove everything this script creates, in FK-safe order.
 */
function resetSeed(\PDO $pdo, string $prefix, InstallConfig $install): void
{
    echo "Reset\n";

    $names = array_map(static fn ($r) => $r['username'], READERS);
    $in    = implode(',', array_fill(0, \count($names), '?'));

    $stmt = $pdo->prepare("SELECT id, username FROM `{$prefix}users` WHERE username IN ({$in})");
    $stmt->execute($names);
    $found = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

    if (!$found) {
        echo "  Nothing to remove.\n";
    }

    $ids = array_keys($found);

    if ($ids) {
        $idIn = implode(',', array_fill(0, \count($ids), '?'));

        foreach (['progress', 'notes', 'group_members', 'users'] as $table) {
            $column = $table === 'group_members' || $table === 'users' ? 'user_id' : 'user_id';
            $pdo->prepare("DELETE FROM `{$prefix}livingword_{$table}` WHERE {$column} IN ({$idIn})")->execute($ids);
        }

        // Clear any pointer left dangling at a reader that is about to vanish.
        $pdo->prepare(
            "UPDATE `{$prefix}livingword_users` SET accountability_partner_id = NULL
              WHERE accountability_partner_id IN ({$idIn})"
        )->execute($ids);
    }

    $tokens  = array_map('groupToken', GROUP_KEYS);
    $tokenIn = implode(',', array_fill(0, \count($tokens), '?'));
    $groups  = $pdo->prepare("SELECT id FROM `{$prefix}livingword_groups` WHERE invite_token IN ({$tokenIn})");
    $groups->execute($tokens);
    $groupIds = $groups->fetchAll(\PDO::FETCH_COLUMN);

    if ($groupIds) {
        $gIn = implode(',', array_fill(0, \count($groupIds), '?'));
        $pdo->prepare("DELETE FROM `{$prefix}livingword_group_members` WHERE group_id IN ({$gIn})")->execute($groupIds);
        $pdo->prepare("DELETE FROM `{$prefix}livingword_groups` WHERE id IN ({$gIn})")->execute($groupIds);
        printf("  %d group(s) removed\n", \count($groupIds));
    }

    // The Joomla accounts go last, through the CLI so usergroup mappings and
    // any other core-owned rows go with them.
    $cli = $install->path . '/cli/joomla.php';

    foreach ($found as $id => $username) {
        if (is_file($cli)) {
            shellJoomla($cli, ['user:delete', '--username=' . $username]);
        }

        printf("  - %s (id %d)\n", $username, $id);
    }
}
