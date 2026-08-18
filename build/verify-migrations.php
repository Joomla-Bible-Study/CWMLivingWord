<?php

/**
 * Release migration verifier — asserts the schema actually landed on every
 * `role = test` install after an install or upgrade.
 *
 * `cwm-verify --target test` only confirms the extension is *registered* at the
 * expected version in #__extensions. It says nothing about the tables, columns
 * and indexes the SQL is supposed to create. This closes that gap: it connects
 * to each role=test install's database — reading credentials straight from that
 * install's configuration.php, since Joomla's CLI bootstrap is unreliable for
 * schema inspection — and checks the DDL.
 *
 * Data-driven: EXPECTATIONS is keyed by version. When a release adds
 * migrations, add an entry describing what it introduces.
 *
 * Version resolution — a release shipping no migrations must not fail the gate,
 * but a release shipping migrations nobody described must:
 *
 *   - exact x.y.z entry exists                  => verify it
 *   - no entry, but update SQL exists for it    => FAIL; expectations forgotten
 *   - no entry and no update SQL                => re-verify the newest earlier
 *                                                  entry as regression cover
 *
 * That last case is the one that earns its keep: a fresh install of a
 * no-migration release still has to carry everything its predecessors
 * introduced, which is what catches install.sql drifting behind the update SQL.
 *
 * Usage:   php build/verify-migrations.php [version]
 *   version  Optional override; defaults to active_development in versions.json.
 *
 * Exit code: 0 = every assertion passed on every test install (or there is
 * genuinely nothing to verify); 1 = one or more failed, no role=test install,
 * or a version shipped migrations with no expectations describing them.
 *
 * @package  Livingword.Build
 * @since    __DEPLOY_VERSION__
 */

declare(strict_types=1);

use CWM\BuildTools\Dev\PropertiesReader;
use CWM\BuildTools\Dev\TestSite;

$root = \dirname(__DIR__);

require $root . '/libraries/vendor/autoload.php';

/**
 * What each release is expected to have added, cumulatively verified.
 *
 * `tables`  — must exist.
 * `columns` — table => [column, ...] that must exist on it.
 * `indexes` — table => [index, ...] that must exist on it, or
 *             table => [index => ['unique' => bool, 'columns' => [...]], ...]
 *             when the index's shape matters and not just its presence. #143 is
 *             why the shape form exists: a site can carry the right index name
 *             alongside a stricter one that still rejects the rows it is meant
 *             to admit, and existence alone reports that site as healthy.
 *
 * Derived from admin/sql/updates/mysql/*.sql. A version with no DDL gets no
 * entry; the resolver falls back to the newest earlier one.
 */
const EXPECTATIONS = [
    '5.1.0' => [
        'tables'  => [],
        'columns' => ['livingword_groups' => ['join_mode']],
        'indexes' => [],
    ],
    '5.2.0' => [
        'tables'  => ['livingword_notes'],
        'columns' => ['livingword_groups' => ['join_mode']],
        'indexes' => [],
    ],
    '5.3.0' => [
        'tables'  => ['livingword_notes'],
        'columns' => [
            'livingword_groups' => ['join_mode'],
            'livingword_users'  => ['action_token'],
        ],
        'indexes' => ['livingword_users' => ['idx_action_token']],
    ],
    '5.4.0' => [
        'tables'  => ['livingword_notes', 'livingword_tools'],
        'columns' => [
            'livingword_groups' => ['join_mode'],
            'livingword_users'  => ['action_token'],
        ],
        'indexes' => ['livingword_users' => ['idx_action_token']],
    ],
    '5.5.0' => [
        'tables'  => ['livingword_notes', 'livingword_tools'],
        'columns' => [
            'livingword_groups' => ['join_mode'],
            'livingword_users'  => ['action_token'],
            'livingword_links'  => ['catid'],
        ],
        'indexes' => [
            'livingword_users' => ['idx_action_token'],
            'livingword_links' => ['idx_catid'],
        ],
    ],
    // 5.6.0 and 5.7.0 ship their schema work in script.php postflight rather
    // than update SQL (see those methods for why), so the entries exist to have
    // the result asserted, not because an update file introduced it.
    '5.7.0' => [
        'tables'  => ['livingword_notes', 'livingword_tools'],
        'columns' => [
            'livingword_groups'   => ['join_mode'],
            'livingword_users'    => ['action_token', 'audio_version', 'email_hour', 'timezone'],
            'livingword_links'    => ['catid'],
            'livingword_progress' => ['passage_index'],
        ],
        'indexes' => [
            'livingword_users'    => ['idx_action_token'],
            'livingword_links'    => ['idx_catid'],
            // #143: the unique key must cover passage_index, and the
            // three-column index must NOT be unique — either one alone still
            // limits the site to a single passage per day.
            'livingword_progress' => [
                'idx_user_plan_day_passage' => [
                    'unique'  => true,
                    'columns' => ['user_id', 'plan_id', 'day', 'passage_index'],
                ],
                'idx_user_plan_day' => [
                    'unique'  => false,
                    'columns' => ['user_id', 'plan_id', 'day'],
                ],
            ],
        ],
    ],
];

/** Every table install.sql is expected to create, regardless of version. */
const BASE_TABLES = [
    'livingword_plans',
    'livingword_plans_details',
    'livingword_users',
    'livingword_progress',
    'livingword_links',
    'livingword_tools',
    'livingword_groups',
    'livingword_group_members',
    'livingword_notes',
];

$version = $argv[1] ?? null;

if ($version === null) {
    $versions = json_decode((string) file_get_contents($root . '/build/versions.json'), true);
    $version  = $versions['active_development']['version'] ?? ($versions['current']['version'] ?? '');
}

if ($version === '') {
    fwrite(STDERR, "Could not resolve a version to verify.\n");

    exit(1);
}

// Pre-release suffixes share their base version's schema: 5.6.0-beta2 ships
// whatever 5.6.0 ships.
$baseVersion = preg_replace('/-.*$/', '', $version) ?? $version;

/**
 * Resolve which expectations apply, per the rules in the file header.
 *
 * @return array{0: string, 1: array}|null  [version, expectations], or null when
 *                                          there is genuinely nothing to check.
 */
$resolve = static function (string $v) use ($root): ?array {
    if (isset(EXPECTATIONS[$v])) {
        return [$v, EXPECTATIONS[$v]];
    }

    $updateSql = $root . '/admin/sql/updates/mysql/' . $v . '.sql';

    // A file that is only comments ships no DDL — 5.6.0 is exactly this.
    $hasDdl = is_file($updateSql)
        && preg_match('/^\s*(ALTER|CREATE|DROP|INSERT|UPDATE|RENAME)\b/mi', (string) file_get_contents($updateSql)) === 1;

    if ($hasDdl) {
        fwrite(STDERR, "FAIL: {$v} ships update SQL but EXPECTATIONS has no entry describing it.\n");
        fwrite(STDERR, "      Add one to build/verify-migrations.php so the migration is actually verified.\n");

        exit(1);
    }

    $earlier = array_filter(
        array_keys(EXPECTATIONS),
        static fn(string $known): bool => version_compare($known, $v, '<')
    );

    if ($earlier === []) {
        return null;
    }

    usort($earlier, 'version_compare');
    $newest = end($earlier);

    return [$newest, EXPECTATIONS[$newest]];
};

$resolved = $resolve($baseVersion);

if ($resolved === null) {
    echo "Nothing to verify for {$version}.\n";

    exit(0);
}

[$appliesFrom, $expected] = $resolved;

$reader   = new PropertiesReader($root . '/build.properties');
$installs = $reader->installsFor('test');

if ($installs === []) {
    fwrite(STDERR, "No role=test install in build.properties — nothing to verify.\n");

    exit(1);
}

echo "Verifying schema for {$version} (expectations from {$appliesFrom})\n";

$failures = 0;

foreach ($installs as $install) {
    echo "\n=== {$install->id} ({$install->path}) ===\n";

    $configFile = $install->path . '/configuration.php';

    if (!is_file($configFile)) {
        fwrite(STDERR, "  configuration.php not found.\n");
        $failures++;

        continue;
    }

    // configuration.php is parsed as text rather than required. Every one of
    // them declares a class named JConfig, and classes are process-global, so
    // requiring a second install's config used to be a fatal redeclare — which
    // is why this went through a shell_exec child process to read it.
    try {
        $site = TestSite::fromPath($install->path);
        $db   = $site->db();
    } catch (\RuntimeException $e) {
        fwrite(STDERR, '  ' . $e->getMessage() . "\n");
        $failures++;

        continue;
    }

    $prefix = $site->prefix();

    $fail = static function (string $message) use (&$failures): void {
        echo "  FAIL: {$message}\n";
        $failures++;
    };

    $tableExists = static fn (string $table): bool => $site->hasTable('#__' . $table);

    $checks = 0;

    // 1. Every table install.sql creates, plus any the migrations add.
    foreach (array_unique([...BASE_TABLES, ...$expected['tables']]) as $table) {
        $checks++;

        if (!$tableExists($table)) {
            $fail("missing table {$prefix}{$table}");
        }
    }

    // 2. Columns introduced by ALTER TABLE.
    foreach ($expected['columns'] as $table => $columns) {
        if (!$tableExists($table)) {
            $fail("missing table {$prefix}{$table} (needed for column checks)");

            continue;
        }

        foreach ($columns as $column) {
            $checks++;
            if (!$site->hasColumn('#__' . $table, $column)) {
                $fail("missing column {$table}.{$column}");
            }
        }
    }

    // 3. Indexes introduced alongside them. A missing index is silent in
    //    normal use and only shows up as a slow query under load — or, when the
    //    index is a unique key, as writes the application quietly loses.
    foreach ($expected['indexes'] as $table => $indexes) {
        if (!$tableExists($table)) {
            continue;
        }

        foreach ($indexes as $key => $value) {
            // Both forms: a bare name, or name => shape.
            $index = \is_int($key) ? $value : $key;
            $shape = \is_int($key) ? [] : $value;

            $checks++;
            if (!$site->hasIndex('#__' . $table, $index)) {
                $fail("missing index {$table}.{$index}");

                continue;
            }

            // Presence is shared; shape is not. Uniqueness and column order are
            // read here because no other consumer has asked for them yet, and a
            // primitive with one caller is a guess about the second.
            $stmt = $db->prepare(
                'SELECT non_unique AS Non_unique, column_name AS Column_name, seq_in_index AS Seq_in_index '
                . 'FROM information_schema.statistics '
                . 'WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?'
            );
            $stmt->execute([$prefix . $table, $index]);
            $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            usort($parts, static fn(array $a, array $b): int => $a['Seq_in_index'] <=> $b['Seq_in_index']);

            if (isset($shape['unique'])) {
                $checks++;
                $isUnique = (int) $parts[0]['Non_unique'] === 0;

                if ($isUnique !== $shape['unique']) {
                    $fail(sprintf(
                        'index %s.%s is %s, expected %s',
                        $table,
                        $index,
                        $isUnique ? 'UNIQUE' : 'non-unique',
                        $shape['unique'] ? 'UNIQUE' : 'non-unique'
                    ));
                }
            }

            if (isset($shape['columns'])) {
                $checks++;
                $columns = array_column($parts, 'Column_name');

                if ($columns !== $shape['columns']) {
                    $fail(sprintf(
                        'index %s.%s covers (%s), expected (%s)',
                        $table,
                        $index,
                        implode(', ', $columns),
                        implode(', ', $shape['columns'])
                    ));
                }
            }
        }
    }

    echo "  {$checks} assertion(s) checked\n";

}

if ($failures > 0) {
    fwrite(STDERR, "\n{$failures} schema assertion(s) failed.\n");

    exit(1);
}

echo "\nSchema verified for {$version}.\n";
