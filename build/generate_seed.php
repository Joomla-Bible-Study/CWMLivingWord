#!/usr/bin/env php
<?php

/**
 * Generates seed.sql for the v5.2.0 schema from the original J3 install SQL.
 *
 * Usage: php build/generate_seed.php > admin/sql/seed.sql
 *
 * @since 5.2.0
 */

define('BASE_DIR', dirname(__DIR__));

// Plan alias → ID mapping
const PLAN_IDS = [
    'bio'     => 1,
    'chron'   => 2,
    'comp'    => 3,
    'newtest' => 4,
    'oldtest' => 5,
    'ontp'    => 6,
    'surv'    => 7,
    'ttb'     => 8,
];

// LWBIBLEBOOK number → English book name
const BOOK_MAP = [
    1  => 'Genesis',        2  => 'Exodus',         3  => 'Leviticus',
    4  => 'Numbers',        5  => 'Deuteronomy',    6  => 'Joshua',
    7  => 'Judges',         8  => 'Ruth',           9  => '1 Samuel',
    10 => '2 Samuel',       11 => '1 Kings',        12 => '2 Kings',
    13 => '1 Chronicles',   14 => '2 Chronicles',   15 => 'Ezra',
    16 => 'Nehemiah',       19 => 'Esther',         22 => 'Job',
    23 => 'Psalm',          24 => 'Proverbs',       25 => 'Ecclesiastes',
    26 => 'Song of Solomon', 29 => 'Isaiah',        30 => 'Jeremiah',
    31 => 'Lamentations',   33 => 'Ezekiel',       34 => 'Daniel',
    35 => 'Hosea',          36 => 'Joel',           37 => 'Amos',
    38 => 'Obadiah',        39 => 'Jonah',          40 => 'Micah',
    41 => 'Nahum',          42 => 'Habakkuk',       43 => 'Zephaniah',
    44 => 'Haggai',         45 => 'Zechariah',      46 => 'Malachi',
    47 => 'Matthew',        48 => 'Mark',           49 => 'Luke',
    50 => 'John',           51 => 'Acts',           52 => 'Romans',
    53 => '1 Corinthians',  54 => '2 Corinthians',  55 => 'Galatians',
    56 => 'Ephesians',      57 => 'Philippians',    58 => 'Colossians',
    59 => '1 Thessalonians', 60 => '2 Thessalonians', 61 => '1 Timothy',
    62 => '2 Timothy',      63 => 'Titus',          64 => 'Philemon',
    65 => 'Hebrews',        66 => 'James',          67 => '1 Peter',
    68 => '2 Peter',        69 => '1 John',         70 => '2 John',
    71 => '3 John',         72 => 'Jude',           73 => 'Revelation',
];

function convertReading(string $reading): string
{
    $parts  = explode(';', $reading);
    $result = [];

    foreach ($parts as $part) {
        $part = trim($part);
        if (empty($part)) {
            continue;
        }
        if (preg_match('/LWBIBLEBOOK(\d+)\s+(.+)/', $part, $m)) {
            $result[] = (BOOK_MAP[(int) $m[1]] ?? 'Unknown') . ' ' . trim($m[2]);
        } else {
            $result[] = $part;
        }
    }

    return implode('; ', $result);
}

function esc(string $v): string
{
    $v = stripslashes($v);

    return str_replace("'", "''", $v);
}

// Read original SQL
$sql = shell_exec('cd ' . escapeshellarg(BASE_DIR) . ' && git show 0bc45f3:Component/admin/sql/install.mysql.utf8.sql 2>/dev/null');

if (empty($sql)) {
    fwrite(STDERR, "ERROR: Could not read original SQL from git\n");
    exit(1);
}

// --- Output ---
echo "-- LivingWord Seed Data (v5.2.0 schema)\n\n";

// Plans
echo "INSERT INTO `#__livingword_plans` (`id`, `alias`, `title`, `description`, `message`, `audio`, `testament`, `published`, `ordering`) VALUES\n";
$planRows = [];

preg_match('/INSERT IGNORE INTO #__livingword_plans\s[^V]*VALUES\s*([\s\S]*?);(?=\s*(?:CREATE|INSERT|$))/m', $sql, $m);
preg_match_all("/\(([^)]+)\)/", $m[1], $tuples);

foreach ($tuples[1] as $t) {
    preg_match_all("/'([^']*)'|NULL|(\d+)/", $t, $vals);
    $v = array_map(fn($x) => trim($x, "'"), $vals[0]);
    if (\count($v) < 10) {
        continue;
    }

    $alias     = $v[1];
    $id        = PLAN_IDS[$alias] ?? 0;
    $desc      = $v[2];
    $message   = $v[3];
    $audio     = (int) $v[4];
    $testament = (int) $v[5];
    $published = (int) $v[6];
    $ordering  = (int) $v[9];

    $planRows[] = sprintf(
        "(%d, '%s', '%s', '%s', '%s', %d, %d, %d, %d)",
        $id,
        esc($alias),
        esc($desc),
        esc($desc),
        esc($message),
        $audio,
        $testament,
        $published,
        $ordering
    );
}

echo implode(",\n", $planRows) . ";\n\n";

// Readings
preg_match('/INSERT IGNORE INTO #__livingword_plans_details\s[^V]*VALUES\s*([\s\S]*?);(?=\s*$)/m', $sql, $m);
preg_match_all("/\(([^)]+)\)/", $m[1], $tuples);

$readings = [];

foreach ($tuples[1] as $t) {
    preg_match_all("/'([^']*)'|NULL|(\d+)/", $t, $vals);
    $v = array_map(fn($x) => trim($x, "'"), $vals[0]);
    if (\count($v) < 9) {
        continue;
    }

    $alias    = $v[1];
    $planId   = PLAN_IDS[$alias] ?? 0;
    $reading  = convertReading($v[2]);
    $ordering = (int) $v[8];

    if ($planId === 0 || empty($reading)) {
        continue;
    }

    $readings[] = sprintf("(%d, %d, '%s', '')", $planId, $ordering, esc($reading));
}

// Output in batches of 100
$batches = array_chunk($readings, 100);

foreach ($batches as $batch) {
    echo "INSERT INTO `#__livingword_plans_details` (`plan_id`, `ordering`, `reading`, `audio`) VALUES\n";
    echo implode(",\n", $batch) . ";\n\n";
}

// Links
echo "INSERT INTO `#__livingword_links` (`name`, `url`, `category`, `target`, `published`, `ordering`) VALUES\n";
echo "('Blue Letter Bible', 'https://www.blueletterbible.org', 'Bible Study', 2, 1, 1),\n";
echo "('Bible Hub', 'https://biblehub.com', 'Bible Study', 2, 1, 2),\n";
echo "('Bible Project', 'https://bibleproject.com', 'Bible Study', 2, 1, 3),\n";
echo "('Got Questions', 'https://www.gotquestions.org', 'Bible Study', 2, 1, 4),\n";
echo "('Enduring Word Commentary', 'https://enduringword.com', 'Bible Study', 2, 1, 5),\n";
echo "('Bible For Children', 'https://www.bibleforchildren.org', 'Just For Kids', 2, 1, 1),\n";
echo "('Keys for Kids', 'https://www.keysforkids.org', 'Just For Kids', 2, 1, 2),\n";
echo "('Superbook Kids', 'https://www.superbook.cbn.com', 'Just For Kids', 2, 1, 3),\n";
echo "('Bible App for Kids', 'https://bibleappforkids.com', 'Just For Kids', 2, 1, 4);\n";

fwrite(STDERR, "Plans: " . \count($planRows) . ", Readings: " . \count($readings) . "\n");
