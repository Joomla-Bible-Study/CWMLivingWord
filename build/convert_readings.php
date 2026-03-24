#!/usr/bin/env php
<?php

/**
 * One-time conversion script: LWBIBLEBOOK format → human-readable passage references.
 *
 * Reads the original Joomla 3 install SQL from git history and outputs clean SQL
 * with human-readable Bible references for use as seed data.
 *
 * Usage: php build/convert_readings.php > admin/sql/seed.sql
 *
 * @since 5.1.0
 */

// LWBIBLEBOOK number → English book name (73-book Catholic/Orthodox canon ordering)
const BOOK_MAP = [
    1  => 'Genesis',
    2  => 'Exodus',
    3  => 'Leviticus',
    4  => 'Numbers',
    5  => 'Deuteronomy',
    6  => 'Joshua',
    7  => 'Judges',
    8  => 'Ruth',
    9  => '1 Samuel',
    10 => '2 Samuel',
    11 => '1 Kings',
    12 => '2 Kings',
    13 => '1 Chronicles',
    14 => '2 Chronicles',
    15 => 'Ezra',
    16 => 'Nehemiah',
    17 => 'Tobit',
    18 => 'Judith',
    19 => 'Esther',
    20 => '1 Maccabees',
    21 => '2 Maccabees',
    22 => 'Job',
    23 => 'Psalm',
    24 => 'Proverbs',
    25 => 'Ecclesiastes',
    26 => 'Song of Solomon',
    27 => 'Wisdom',
    28 => 'Sirach',
    29 => 'Isaiah',
    30 => 'Jeremiah',
    31 => 'Lamentations',
    32 => 'Baruch',
    33 => 'Ezekiel',
    34 => 'Daniel',
    35 => 'Hosea',
    36 => 'Joel',
    37 => 'Amos',
    38 => 'Obadiah',
    39 => 'Jonah',
    40 => 'Micah',
    41 => 'Nahum',
    42 => 'Habakkuk',
    43 => 'Zephaniah',
    44 => 'Haggai',
    45 => 'Zechariah',
    46 => 'Malachi',
    47 => 'Matthew',
    48 => 'Mark',
    49 => 'Luke',
    50 => 'John',
    51 => 'Acts',
    52 => 'Romans',
    53 => '1 Corinthians',
    54 => '2 Corinthians',
    55 => 'Galatians',
    56 => 'Ephesians',
    57 => 'Philippians',
    58 => 'Colossians',
    59 => '1 Thessalonians',
    60 => '2 Thessalonians',
    61 => '1 Timothy',
    62 => '2 Timothy',
    63 => 'Titus',
    64 => 'Philemon',
    65 => 'Hebrews',
    66 => 'James',
    67 => '1 Peter',
    68 => '2 Peter',
    69 => '1 John',
    70 => '2 John',
    71 => '3 John',
    72 => 'Jude',
    73 => 'Revelation',
];

/**
 * Convert a single LWBIBLEBOOK reference to human-readable format.
 *
 * Input:  "LWBIBLEBOOK25 1-3;LWBIBLEBOOK50 12"
 * Output: "Ecclesiastes 1-3; John 12"
 */
function convertReading(string $reading): string
{
    if (empty($reading)) {
        return '';
    }

    $parts  = explode(';', $reading);
    $result = [];

    foreach ($parts as $part) {
        $part = trim($part);

        if (empty($part)) {
            continue;
        }

        // Match LWBIBLEBOOK<num> <chapters/verses>
        if (preg_match('/LWBIBLEBOOK(\d+)\s+(.+)/', $part, $matches)) {
            $bookNum  = (int) $matches[1];
            $chapters = trim($matches[2]);
            $bookName = BOOK_MAP[$bookNum] ?? 'Unknown(' . $bookNum . ')';
            $result[] = $bookName . ' ' . $chapters;
        } else {
            // Already human-readable (e.g., "Obadiah 1")
            $result[] = $part;
        }
    }

    return implode('; ', $result);
}

/**
 * Escape a string for SQL INSERT values.
 */
function sqlEscape(string $value): string
{
    // Remove backslash escapes from original data, then double-quote for SQL
    $value = str_replace("\\'", "'", $value);
    $value = stripslashes($value);

    return str_replace("'", "''", $value);
}

// --- Main ---

$baseDir = dirname(__DIR__);

// Read original install SQL from git
$originalSql = shell_exec('cd ' . escapeshellarg($baseDir) . ' && git show 0bc45f3:Component/admin/sql/install.mysql.utf8.sql 2>/dev/null');

if (empty($originalSql)) {
    fwrite(STDERR, "ERROR: Could not read original SQL from git history.\n");
    fwrite(STDERR, "Make sure you're in the CWMLivingWord repo with commit 0bc45f3 available.\n");
    exit(1);
}

// Parse plans
preg_match('/INSERT IGNORE INTO #__livingword_plans\s[^V]*VALUES\s*([\s\S]*?);(?=\s*(?:CREATE|INSERT|$))/m', $originalSql, $plansMatch);
// Parse plan details
preg_match('/INSERT IGNORE INTO #__livingword_plans_details\s[^V]*VALUES\s*([\s\S]*?);(?=\s*$)/m', $originalSql, $detailsMatch);
// Parse links
preg_match('/INSERT IGNORE INTO #__livingword_links\s[^V]*VALUES\s*([\s\S]*?);(?=\s*(?:CREATE|INSERT|$))/m', $originalSql, $linksMatch);

if (empty($plansMatch[1]) || empty($detailsMatch[1]) || empty($linksMatch[1])) {
    fwrite(STDERR, "ERROR: Could not parse INSERT statements from original SQL.\n");
    exit(1);
}

// Output header
echo "-- LivingWord Seed Data\n";
echo "-- Converted from LWBIBLEBOOK format to human-readable passage references\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
echo "-- Source: git commit 0bc45f3 (original Joomla 3 install SQL)\n\n";

// --- Plans ---
echo "-- Reading Plans\n";
$planRows = [];
preg_match_all("/\(([^)]+)\)/", $plansMatch[1], $planTuples);

foreach ($planTuples[1] as $tuple) {
    // Parse: 'id','name','description','message','audio','newtest','published','datetime','checked_out','ordering'
    preg_match_all("/'([^']*)'|NULL|(\d+)/", $tuple, $vals);
    $values = [];
    foreach ($vals[0] as $v) {
        $values[] = trim($v, "'");
    }

    if (count($values) < 10) {
        continue;
    }

    $name        = $values[1];
    $description = $values[2];
    $message     = $values[3];
    $audio       = (int) $values[4];
    $newtest     = (int) $values[5];
    $published   = (int) $values[6];
    $ordering    = (int) $values[9];

    $planRows[] = sprintf(
        "('%s', '%s', '%s', %d, %d, %d, %d)",
        sqlEscape($name),
        sqlEscape($description),
        sqlEscape($message),
        $audio,
        $newtest,
        $published,
        $ordering
    );
}

echo "INSERT INTO `#__livingword_plans` (`name`, `description`, `message`, `audio`, `newtest`, `published`, `ordering`) VALUES\n";
echo implode(",\n", $planRows) . ";\n\n";

// --- Plan Details (Readings) ---
echo "-- Daily Readings (converted from LWBIBLEBOOK format)\n";

$detailRows = [];
$readingCount = 0;
preg_match_all("/\(([^)]+)\)/", $detailsMatch[1], $detailTuples);

foreach ($detailTuples[1] as $tuple) {
    preg_match_all("/'([^']*)'|NULL|(\d+)/", $tuple, $vals);
    $values = [];
    foreach ($vals[0] as $v) {
        $values[] = trim($v, "'");
    }

    if (count($values) < 9) {
        continue;
    }

    $plan     = $values[1];
    $reading  = $values[2];
    $audio    = $values[3];
    $ordering = (int) $values[8];

    // Convert LWBIBLEBOOK references to human-readable
    $converted = convertReading($reading);

    if (empty($converted)) {
        fwrite(STDERR, "WARNING: Empty reading for plan=$plan ordering=$ordering\n");
        continue;
    }

    $detailRows[] = sprintf(
        "('%s', '%s', '%s', %d)",
        sqlEscape($plan),
        sqlEscape($converted),
        sqlEscape($audio),
        $ordering
    );
    $readingCount++;
}

// Output in batches of 100 for readability
$batches = array_chunk($detailRows, 100);
foreach ($batches as $i => $batch) {
    echo "INSERT INTO `#__livingword_plans_details` (`plan`, `reading`, `audio`, `ordering`) VALUES\n";
    echo implode(",\n", $batch) . ";\n\n";
}

// --- Links ---
echo "-- Resource Links\n";
$linkRows = [];
preg_match_all("/\(([^)]+)\)/", $linksMatch[1], $linkTuples);

foreach ($linkTuples[1] as $tuple) {
    preg_match_all("/'([^']*)'|NULL|(\d+)/", $tuple, $vals);
    $values = [];
    foreach ($vals[0] as $v) {
        $values[] = trim($v, "'");
    }

    if (count($values) < 9) {
        continue;
    }

    $name      = $values[1];
    $url       = $values[2];
    $category  = $values[3];
    $target    = (int) $values[4];
    $published = (int) $values[5];
    $ordering  = (int) $values[8];

    // Update URLs to https
    $url = preg_replace('/^http:\/\//', 'https://', $url);

    $linkRows[] = sprintf(
        "('%s', '%s', '%s', %d, %d, %d)",
        sqlEscape($name),
        sqlEscape($url),
        sqlEscape($category),
        $target,
        $published,
        $ordering
    );
}

echo "INSERT INTO `#__livingword_links` (`name`, `url`, `category`, `target`, `published`, `ordering`) VALUES\n";
echo implode(",\n", $linkRows) . ";\n";

// Summary to stderr
fwrite(STDERR, "\nConversion complete:\n");
fwrite(STDERR, "  Plans:    " . count($planRows) . "\n");
fwrite(STDERR, "  Readings: $readingCount\n");
fwrite(STDERR, "  Links:    " . count($linkRows) . "\n");
