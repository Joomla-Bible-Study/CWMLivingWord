#!/usr/bin/env php
<?php

// phpcs:disable PSR1.Files.SideEffects
/**
 * LivingWord Build Script
 * Adapted from Proclaim build system
 */

const BASE_DIR        = __DIR__ . '/..';
const BUILD_DIR       = BASE_DIR . '/build';
const PROPERTIES_FILE = BASE_DIR . '/build.properties';

$command = $argv[1] ?? 'help';
$verbose = \in_array('--verbose', $argv, true) || \in_array('-v', $argv, true);

try {
    switch ($command) {
        case 'setup':
            doSetup();
            break;
        case 'link':
            doLink(verbose: $verbose);
            break;
        case 'check':
            doCheckLinks($verbose);
            break;
        case 'clean':
            doClean($verbose);
            break;
        case 'build':
            doBuild($verbose);
            break;
        case 'install-joomla':
            doInstallJoomla();
            break;
        case 'joomla-latest':
            doJoomlaLatest();
            break;
        case 'verify':
            doVerifyExtensions($verbose);
            break;
        case 'lint-syntax':
            doLintSyntax($verbose);
            break;
        case 'help':
        default:
            showHelp();
            break;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Displays the help message with available commands.
 *
 * @return void
 * @since 5.0.0
 */
function showHelp(): void
{
    echo "LivingWord Build Tool\n";
    echo "Usage: php build/livingword_build.php [command]\n\n";
    echo "Commands:\n";
    echo "  setup           Interactive setup wizard for build.properties\n";
    echo "  link            Setup symbolic links to local Joomla installation(s)\n";
    echo "  check           Validate all symlinks are healthy\n";
    echo "  clean           Remove symbolic links (clean dev state)\n";
    echo "  build           Build component package (zip)\n";
    echo "  install-joomla  Download and install Joomla\n";
    echo "  joomla-latest   Show latest available Joomla version\n";
    echo "  verify          Verify all sub-extensions are registered in dev Joomla DB(s)\n";
    echo "  lint-syntax     Check PHP syntax errors\n";
    echo "\nOptions:\n";
    echo "  -v, --verbose   Show detailed output (e.g., each symlink path)\n";
    echo "\nMultiple Joomla paths are supported via builder.joomla_paths (comma-separated)\n";
    echo "in build.properties. The singular builder.joomla_path is also supported.\n";
}

/**
 * Reads and parses the build.properties file.
 *
 * @return array Associative array of properties.
 * @throws Exception If build.properties does not exist.
 * @since 5.0.0
 */
function getProperties(): array
{
    if (!file_exists(PROPERTIES_FILE)) {
        throw new \RuntimeException("build.properties not found. Run 'composer setup' first.");
    }
    $lines = file(PROPERTIES_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $props = [];
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value]     = explode('=', $line, 2);
        $props[trim($key)] = trim($value);
    }
    return $props;
}

/**
 * Returns an array of Joomla installation paths from build.properties.
 *
 * @param   array  $props  Properties array from getProperties().
 *
 * @return array List of resolved Joomla paths.
 * @since 5.0.0
 */
function getJoomlaPaths(array $props): array
{
    $raw = '';

    if (!empty($props['builder.joomla_paths'])) {
        $raw = $props['builder.joomla_paths'];
    } elseif (!empty($props['builder.joomla_path'])) {
        $raw = $props['builder.joomla_path'];
    }

    if ($raw === '') {
        return [];
    }

    $dir   = trim($props['builder.joomla_dir'] ?? '', '/');
    $paths = [];

    foreach (explode(',', $raw) as $entry) {
        $entry = trim($entry);
        if ($entry === '') {
            continue;
        }
        $path = rtrim($entry, '/');
        if ($dir !== '') {
            $path .= '/' . $dir;
        }
        $paths[] = $path;
    }

    return $paths;
}

/**
 * Returns the list of external symlink mappings for a given Joomla path.
 *
 * @param   string  $joomlaPath  The resolved Joomla installation path.
 *
 * @return array Associative array of target => link.
 * @since 5.0.0
 */
function getExternalLinks(string $joomlaPath): array
{
    $base = realpath(BASE_DIR) ?: BASE_DIR;

    return [
        "$base/media/com_livingword"                        => "$joomlaPath/media/com_livingword",
        "$base/admin"                                       => "$joomlaPath/administrator/components/com_livingword",
        "$base/site"                                        => "$joomlaPath/components/com_livingword",
        "$base/mod_livingword"                              => "$joomlaPath/modules/mod_livingword",
        "$base/plg_task_livingword"                         => "$joomlaPath/plugins/task/livingword",
        "$base/admin/language/en-GB/com_livingword.ini"     => "$joomlaPath/administrator/language/en-GB/com_livingword.ini",
        "$base/admin/language/en-GB/com_livingword.sys.ini" => "$joomlaPath/administrator/language/en-GB/com_livingword.sys.ini",
        "$base/site/language/en-GB/com_livingword.ini"      => "$joomlaPath/language/en-GB/com_livingword.ini",
    ];
}

/**
 * Prompts the user for input via STDIN.
 *
 * @param   string       $question  The question to ask.
 * @param   string|null  $default   The default value if no input is provided.
 * @param   int          $timeout   Seconds to wait before auto-accepting the default (0 = no timeout).
 *
 * @return string|null The user's input or the default value.
 * @since 5.0.0
 */
function ask(string $question, string|null $default = null, int $timeout = 0): string|null
{
    $prompt = $question . ($default ? " [$default]" : '');

    if ($timeout > 0 && $default !== null && stream_isatty(STDIN)) {
        $oldStty = trim((string) shell_exec('stty -g 2>/dev/null'));
        system('stty cbreak -echo 2>/dev/null');

        for ($remaining = $timeout; $remaining > 0; $remaining--) {
            echo "\r" . $prompt . " ({$remaining}s): ";

            $read   = [STDIN];
            $write  = null;
            $except = null;
            $ready  = @stream_select($read, $write, $except, 1);

            if ($ready > 0) {
                $char = fread(STDIN, 1);
                system('stty ' . escapeshellarg($oldStty) . ' 2>/dev/null');
                echo "\r" . $prompt . ': ' . $char . "    \n";
                return $char === '' ? $default : $char;
            }
        }

        system('stty ' . escapeshellarg($oldStty) . ' 2>/dev/null');
        echo "\r" . $prompt . ': ' . $default . " (auto)\n";
        return $default;
    }

    echo $prompt . ': ';

    $handle = fopen('php://stdin', 'rb');
    $line   = fgets($handle);
    fclose($handle);
    $line = trim($line);
    return $line === '' ? $default : $line;
}

/**
 * Runs the interactive setup wizard to configure build.properties.
 *
 * @return void
 * @throws Exception
 * @since 5.0.0
 */
function doSetup(): void
{
    echo "=== LivingWord Development Setup Wizard ===\n\n";

    $currentProps = file_exists(PROPERTIES_FILE) ? getProperties() : [];

    $existingPaths = '';
    if (!empty($currentProps['builder.joomla_paths'])) {
        $existingPaths = $currentProps['builder.joomla_paths'];
    } elseif (!empty($currentProps['builder.joomla_path'])) {
        $existingPaths = $currentProps['builder.joomla_path'];
    }

    echo "Enter Joomla installation paths (one per prompt, blank when done):\n";
    if ($existingPaths !== '') {
        echo "  Current: $existingPaths\n";
    }

    $existing = $existingPaths !== ''
        ? array_map('trim', explode(',', $existingPaths))
        : [];

    $paths = [];
    $i     = 1;
    while (true) {
        $default = $existing[$i - 1] ?? null;
        $label   = "  Joomla path #$i";
        if ($default === null && $i > 1) {
            $label .= ' (blank to finish)';
        }
        $path = ask($label, $default);
        if ($path === null || $path === '') {
            break;
        }
        $paths[] = $path;
        $i++;
    }

    if (\count($paths) === 0) {
        echo "No paths entered. At least one Joomla path is required.\n";
        return;
    }

    $joomlaDir     = ask('Enter subdirectory within Joomla path (leave empty if none)', $currentProps['builder.joomla_dir'] ?? '');
    $joomlaVersion = ask('Enter the default Joomla version for testing', $currentProps['joomla.version'] ?? '5.4.2');

    $sites      = ['j5dev' => 'Joomla 5', 'j6dev' => 'Joomla 6'];
    $siteConfig = [];

    foreach ($sites as $key => $label) {
        echo "\n--- $label Development Site ---\n";
        $prefix           = "builder.$key";
        $siteConfig[$key] = [
            'url'      => ask("$label site URL", $currentProps["$prefix.url"] ?? "https://$key.local:8890"),
            'db_host'  => ask("$label database host", $currentProps["$prefix.db_host"] ?? 'localhost'),
            'db_user'  => ask("$label database username", $currentProps["$prefix.db_user"] ?? ''),
            'db_pass'  => ask("$label database password", $currentProps["$prefix.db_pass"] ?? ''),
            'db_name'  => ask("$label database name", $currentProps["$prefix.db_name"] ?? ''),
            'username' => ask("$label admin username", $currentProps["$prefix.username"] ?? 'admin'),
            'password' => ask("$label admin password", $currentProps["$prefix.password"] ?? 'admin'),
            'email'    => ask("$label admin email", $currentProps["$prefix.email"] ?? 'admin@example.com'),
        ];
    }

    $distFile = BASE_DIR . '/build.dist.properties';

    if (!file_exists($distFile)) {
        throw new \RuntimeException('build.dist.properties not found.');
    }

    $content = file_get_contents($distFile);

    $replacements = [
        'builder.joomla_paths' => implode(',', $paths),
        'builder.joomla_dir'   => $joomlaDir,
        'joomla.version'       => $joomlaVersion,
    ];

    foreach ($siteConfig as $key => $cfg) {
        $prefix                           = "builder.$key";
        $replacements["$prefix.url"]      = $cfg['url'];
        $replacements["$prefix.db_host"]  = $cfg['db_host'];
        $replacements["$prefix.db_user"]  = $cfg['db_user'];
        $replacements["$prefix.db_pass"]  = $cfg['db_pass'];
        $replacements["$prefix.db_name"]  = $cfg['db_name'];
        $replacements["$prefix.username"] = $cfg['username'];
        $replacements["$prefix.password"] = $cfg['password'];
        $replacements["$prefix.email"]    = $cfg['email'];
    }

    foreach ($replacements as $key => $value) {
        $content = preg_replace(
            '/^(' . preg_quote($key, '/') . ')=.*$/m',
            '$1=' . $value,
            $content
        );
    }

    file_put_contents(PROPERTIES_FILE, $content);
    echo "\nConfiguration saved to build.properties\n";

    $install = ask('Do you want to download and install Joomla? (y/n)', 'n');
    if (strtolower($install) === 'y') {
        doInstallJoomla();
    }
}

/**
 * Creates symbolic links between the project and a local Joomla installation.
 *
 * @param   bool  $quiet    If true, suppresses non-error output.
 * @param   bool  $verbose  If true, shows each symlink path.
 *
 * @return void
 * @throws Exception If no Joomla paths are configured.
 * @since 5.0.0
 */
function doLink(bool $quiet = false, bool $verbose = false): void
{
    $props       = getProperties();
    $joomlaPaths = getJoomlaPaths($props);

    if (\count($joomlaPaths) === 0) {
        throw new \RuntimeException('No Joomla paths configured. Run \'composer setup\' first.');
    }

    if (!$quiet) {
        echo "Internal links created.\n";
    }

    $silent = !$verbose;
    $linked = 0;
    foreach ($joomlaPaths as $joomlaPath) {
        if (!is_dir($joomlaPath)) {
            echo "WARNING: Path not found, skipping: $joomlaPath\n";
            continue;
        }

        if (!$quiet) {
            echo "\nLinked to: $joomlaPath\n";
        }

        foreach (getExternalLinks($joomlaPath) as $target => $link) {
            symlink_force($target, $link, $silent);
        }

        if (!$quiet && !$verbose) {
            echo "  Component:  admin, site, media\n";
            echo "  Modules:    mod_livingword\n";
            echo "  Plugins:    task/livingword\n";
            echo "  Language:   com_livingword.ini, com_livingword.sys.ini\n";
        }
        $linked++;
    }

    if (!$quiet) {
        echo "\nDone! Symlinks created for $linked Joomla installation" . ($linked !== 1 ? 's' : '') . ".\n";
    }
}

/**
 * Forces creation of a symbolic link, removing any existing file or directory at the link path.
 *
 * @param   string  $target  The target path the link should point to.
 * @param   string  $link    The path where the link should be created.
 * @param   bool    $quiet   If true, suppresses success messages.
 *
 * @return void
 * @since 5.0.0
 */
function symlink_force(string $target, string $link, bool $quiet = false): void
{
    clearstatcache(true, $link);

    if (is_link($link)) {
        if (!@unlink($link)) {
            echo "WARNING: Failed to unlink symlink $link\n";
        }
    } elseif (file_exists($link)) {
        if (is_dir($link)) {
            if (PHP_OS_FAMILY === 'Windows') {
                exec('rmdir /s /q ' . escapeshellarg($link), $output, $returnVar);
            } else {
                exec('rm -rf ' . escapeshellarg($link), $output, $returnVar);
            }
            if (isset($returnVar) && $returnVar !== 0) {
                echo "WARNING: Failed to remove directory $link\n";
            }
        } elseif (!@unlink($link)) {
            echo "WARNING: Failed to unlink file $link\n";
        }
    }

    $parent = \dirname($link);
    if (!is_dir($parent) && !mkdir($parent, 0777, true) && !is_dir($parent)) {
        echo "ERROR: Failed to create parent directory $parent\n";
        return;
    }

    if (!$quiet) {
        echo "Linking $link -> $target\n";
    }

    if (!@symlink($target, $link)) {
        echo "ERROR: Failed to create symlink $link -> $target\n";
        $e = error_get_last();
        if ($e) {
            echo '  Details: ' . $e['message'] . "\n";
        }
    }
}

/**
 * Validates all symlinks are healthy across configured Joomla installations.
 *
 * Reports: MISSING (not created), STALE (real file instead of symlink),
 * WRONG (points to wrong target), BROKEN (target doesn't exist), or OK.
 *
 * @param   bool  $verbose  Show healthy links too
 *
 * @return  void
 *
 * @since  5.2.0
 */
function doCheckLinks(bool $verbose = false): void
{
    $props       = getProperties();
    $joomlaPaths = getJoomlaPaths($props);

    if (\count($joomlaPaths) === 0) {
        throw new \RuntimeException('No Joomla paths configured. Run \'composer setup\' first.');
    }

    $issues = 0;

    foreach ($joomlaPaths as $joomlaPath) {
        echo "\nJoomla: $joomlaPath\n";

        if (!is_dir($joomlaPath)) {
            echo "  MISSING: Joomla path does not exist\n";
            $issues++;
            continue;
        }

        foreach (getExternalLinks($joomlaPath) as $target => $link) {
            $issues += checkOneLink($target, $link, $verbose);
        }
    }

    echo "\n" . ($issues === 0
        ? "All symlinks are healthy.\n"
        : "$issues issue(s) found. Run 'composer symlink' to fix.\n");

    if ($issues > 0) {
        exit(1);
    }
}

/**
 * Checks a single symlink and reports its status.
 *
 * @param   string  $target   Expected target path
 * @param   string  $link     Symlink path to check
 * @param   bool    $verbose  Show healthy links too
 *
 * @return  int  Number of issues (0 or 1)
 *
 * @since  5.2.0
 */
function checkOneLink(string $target, string $link, bool $verbose): int
{
    clearstatcache(true, $link);

    if (!is_link($link)) {
        if (file_exists($link)) {
            echo "  STALE:   $link (real file/dir, not a symlink)\n";
        } else {
            echo "  MISSING: $link\n";
        }

        return 1;
    }

    $actual = readlink($link);

    $resolvedActual = realpath($actual) ?: $actual;
    $resolvedTarget = realpath($target) ?: $target;

    if ($resolvedActual !== $resolvedTarget) {
        echo "  WRONG:   $link -> $actual (expected $target)\n";

        return 1;
    }

    if (!file_exists($link)) {
        echo "  BROKEN:  $link -> $target (target does not exist)\n";

        return 1;
    }

    if ($verbose) {
        echo "  OK:      $link\n";
    }

    return 0;
}

/**
 * Read Joomla configuration.php and return DB connection details + table prefix.
 *
 * @param   string  $joomlaPath  Path to Joomla installation root
 *
 * @return  array{host: string, user: string, password: string, db: string, dbprefix: string}|null
 *
 * @since  5.0.0
 */
function getJoomlaDbConfig(string $joomlaPath): ?array
{
    $configFile = $joomlaPath . '/configuration.php';

    if (!file_exists($configFile)) {
        return null;
    }

    $content = file_get_contents($configFile);
    $content = str_replace('class JConfig', 'class JConfig_' . md5($joomlaPath), $content);
    $content = str_replace('<?php', '', $content);
    eval($content);

    $className = 'JConfig_' . md5($joomlaPath);

    if (!class_exists($className)) {
        return null;
    }

    $config = new $className();

    return [
        'host'     => $config->host ?? 'localhost',
        'user'     => $config->user ?? '',
        'password' => $config->password ?? '',
        'db'       => $config->db ?? '',
        'dbprefix' => $config->dbprefix ?? 'jos_',
    ];
}

/**
 * Verify and register all LivingWord sub-extensions in each dev Joomla database.
 *
 * @param   bool  $verbose  Show detailed output
 *
 * @return  void
 *
 * @since  5.0.0
 */
function doVerifyExtensions(bool $verbose = false): void
{
    $props       = getProperties();
    $joomlaPaths = getJoomlaPaths($props);

    if (\count($joomlaPaths) === 0) {
        throw new \RuntimeException('No Joomla paths configured. Run \'composer setup\' first.');
    }

    $expected = [
        ['type' => 'plugin', 'element' => 'livingword', 'name' => 'plg_task_livingword', 'folder' => 'task', 'enabled' => 1, 'locked' => 0],
        ['type' => 'component', 'element' => 'com_livingword', 'name' => 'com_livingword', 'folder' => '', 'enabled' => 1, 'locked' => 0],
    ];

    foreach ($joomlaPaths as $joomlaPath) {
        if (!is_dir($joomlaPath)) {
            echo "WARNING: Path not found, skipping: $joomlaPath\n";
            continue;
        }

        echo "\n=== Verifying: $joomlaPath ===\n";

        $dbConfig = getJoomlaDbConfig($joomlaPath);

        if ($dbConfig === null) {
            echo "  ERROR: Could not read configuration.php\n";
            continue;
        }

        try {
            $pdo = new PDO(
                'mysql:host=' . $dbConfig['host'] . ';dbname=' . $dbConfig['db'] . ';charset=utf8mb4',
                $dbConfig['user'],
                $dbConfig['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            echo "  ERROR: DB connection failed: " . $e->getMessage() . "\n";
            continue;
        }

        $prefix  = $dbConfig['dbprefix'];
        $ok      = 0;
        $fixed   = 0;
        $errors  = 0;

        $nsCheck      = $pdo->query("SHOW COLUMNS FROM {$prefix}extensions LIKE 'namespace'");
        $hasNamespace = $nsCheck && $nsCheck->rowCount() > 0;

        foreach ($expected as $ext) {
            $type    = $ext['type'];
            $element = $ext['element'];
            $folder  = $ext['folder'];
            $name    = $ext['name'];

            $sql    = "SELECT extension_id, enabled, locked FROM {$prefix}extensions WHERE type = ? AND element = ?";
            $params = [$type, $element];

            if ($type === 'plugin' && $folder !== '') {
                $sql .= ' AND folder = ?';
                $params[] = $folder;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $needsUpdate = false;
                $updates     = [];

                if ((int) $row['enabled'] !== $ext['enabled']) {
                    $updates[]   = "enabled = {$ext['enabled']}";
                    $needsUpdate = true;
                }

                if ($ext['locked'] && (int) $row['locked'] !== 1) {
                    $updates[]   = 'locked = 1';
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    $updateSql = "UPDATE {$prefix}extensions SET " . implode(', ', $updates)
                        . " WHERE extension_id = " . (int) $row['extension_id'];
                    $pdo->exec($updateSql);
                    echo "  FIXED:  $name ($type) — updated " . implode(', ', $updates) . "\n";
                    $fixed++;
                } else {
                    if ($verbose) {
                        echo "  OK:     $name ($type)\n";
                    }
                    $ok++;
                }
            } else {
                if ($type === 'plugin') {
                    if ($hasNamespace) {
                        $namespace = match ($folder) {
                            'task' => 'CWM\\\\Plugin\\\\Task\\\\Livingword',
                            default => '',
                        };
                        $insertSql = "INSERT INTO {$prefix}extensions "
                            . "(name, type, element, folder, client_id, enabled, access, locked, manifest_cache, params, custom_data, namespace) "
                            . "VALUES (?, 'plugin', ?, ?, 0, ?, 1, 0, '{}', '{}', '', ?)";
                        $stmt = $pdo->prepare($insertSql);
                        $stmt->execute([$name, $element, $folder, $ext['enabled'], $namespace]);
                    } else {
                        $insertSql = "INSERT INTO {$prefix}extensions "
                            . "(name, type, element, folder, client_id, enabled, access, locked, manifest_cache, params, custom_data) "
                            . "VALUES (?, 'plugin', ?, ?, 0, ?, 1, 0, '{}', '{}', '')";
                        $stmt = $pdo->prepare($insertSql);
                        $stmt->execute([$name, $element, $folder, $ext['enabled']]);
                    }
                    echo "  ADDED:  $name (plugin/$folder)\n";
                    $fixed++;
                } elseif ($type === 'component') {
                    registerComponent($pdo, $prefix, $hasNamespace, $joomlaPath, $verbose);
                    $fixed++;
                }
            }
        }

        // Always ensure namespace map is up to date
        updateNamespaceMap($joomlaPath, $verbose);

        echo "  Summary: $ok OK, $fixed fixed, $errors errors\n";
    }

    echo "\nDone.\n";
}

/**
 * Registers the com_livingword component in a Joomla database.
 *
 * Creates the extensions row, asset record, admin menu items,
 * schema version entry, and runs the install SQL to create tables.
 *
 * @param   PDO     $pdo           Database connection
 * @param   string  $prefix        Table prefix
 * @param   bool    $hasNamespace  Whether the extensions table has a namespace column
 * @param   string  $joomlaPath    Path to the Joomla installation
 * @param   bool    $verbose       Show detailed output
 *
 * @return  void
 *
 * @since  5.0.0
 */
function registerComponent(PDO $pdo, string $prefix, bool $hasNamespace, string $joomlaPath, bool $verbose = false): void
{
    try {
        // 1. Insert into #__extensions
        $columns = 'name, type, element, folder, client_id, enabled, access, locked, manifest_cache, params, custom_data';
        $values  = "?, 'component', 'com_livingword', '', 1, 1, 1, 0, '{}', '{}', ''";

        if ($hasNamespace) {
            $columns .= ', namespace';
            $values  .= ', ?';
            $stmt = $pdo->prepare("INSERT INTO {$prefix}extensions ($columns) VALUES ($values)");
            $stmt->execute(['com_livingword', 'CWM\\Component\\Livingword']);
        } else {
            $stmt = $pdo->prepare("INSERT INTO {$prefix}extensions ($columns) VALUES ($values)");
            $stmt->execute(['com_livingword']);
        }

        $extensionId = (int) $pdo->lastInsertId();

        if ($verbose) {
            echo "    extensions row: ID $extensionId\n";
        }

        // 2. Insert into #__assets
        $stmt = $pdo->prepare(
            "INSERT INTO {$prefix}assets (parent_id, lft, rgt, level, name, title, rules) "
            . "VALUES (1, 0, 0, 1, 'com_livingword', 'com_livingword', '{}')"
        );
        $stmt->execute();
        $assetId = (int) $pdo->lastInsertId();

        // Rebuild lft/rgt: place after last existing asset
        $maxRgt = (int) $pdo->query("SELECT MAX(rgt) FROM {$prefix}assets")->fetchColumn();
        $pdo->exec("UPDATE {$prefix}assets SET lft = " . ($maxRgt + 1) . ", rgt = " . ($maxRgt + 2) . " WHERE id = $assetId");

        if ($verbose) {
            echo "    asset row: ID $assetId\n";
        }

        // 3. Create admin menu items from manifest
        $menuData = [
            ['title' => 'COM_LIVINGWORD', 'link' => 'index.php?option=com_livingword', 'alias' => 'com-livingword', 'level' => 1, 'parent' => 1, 'img' => 'class:component'],
            ['title' => 'COM_LIVINGWORD_CPANEL', 'link' => 'index.php?option=com_livingword&view=cwmcpanel', 'alias' => 'com-livingword-cpanel', 'level' => 2],
            ['title' => 'COM_LIVINGWORD_MANAGE_PLANS', 'link' => 'index.php?option=com_livingword&view=cwmplans', 'alias' => 'com-livingword-plans', 'level' => 2],
            ['title' => 'COM_LIVINGWORD_MANAGE_LINKS', 'link' => 'index.php?option=com_livingword&view=cwmlinks', 'alias' => 'com-livingword-links', 'level' => 2],
            ['title' => 'COM_LIVINGWORD_MANAGE_SUBSCRIBERS', 'link' => 'index.php?option=com_livingword&view=cwmusers', 'alias' => 'com-livingword-users', 'level' => 2],
        ];

        $parentMenuId = 0;
        $menuCount    = 0;

        foreach ($menuData as $item) {
            $parentId = ($item['level'] === 1) ? 1 : $parentMenuId;
            $img      = $item['img'] ?? '';

            $stmt = $pdo->prepare(
                "INSERT INTO {$prefix}menu "
                . "(menutype, title, alias, link, type, published, parent_id, level, component_id, client_id, img, path, access, params, language) "
                . "VALUES ('main', ?, ?, ?, 'component', 1, ?, ?, ?, 1, ?, ?, 1, '', '*')"
            );
            $stmt->execute([
                $item['title'],
                $item['alias'],
                $item['link'],
                $parentId,
                $item['level'],
                $extensionId,
                $img,
                $item['alias'],
            ]);

            $menuId = (int) $pdo->lastInsertId();

            if ($item['level'] === 1) {
                $parentMenuId = $menuId;
            } else {
                $pdo->exec("UPDATE {$prefix}menu SET path = 'com-livingword/{$item['alias']}' WHERE id = $menuId");
            }

            $menuCount++;
        }

        if ($verbose) {
            echo "    menu items: $menuCount created\n";
        }

        // 4. Run install SQL to create tables (DDL auto-commits, so no transaction)
        $sqlFile = BASE_DIR . '/admin/sql/install.mysql.utf8.sql';

        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $sql = str_replace('#__', $prefix, $sql);

            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                fn(string $s): bool => $s !== ''
            );

            $tableCount = 0;
            foreach ($statements as $statement) {
                $pdo->exec($statement);
                $tableCount++;
            }

            if ($verbose) {
                echo "    tables: $tableCount statements executed\n";
            }
        }

        // 5. Insert schema version
        $schemaVersion = '5.0.0';
        $xmlFile       = BASE_DIR . '/livingword.xml';

        if (file_exists($xmlFile)) {
            $xml = simplexml_load_string(file_get_contents($xmlFile));
            if ($xml && isset($xml->version)) {
                $schemaVersion = (string) $xml->version;
            }
        }

        $stmt = $pdo->prepare(
            "INSERT INTO {$prefix}schemas (extension_id, version_id) VALUES (?, ?)"
        );
        $stmt->execute([$extensionId, $schemaVersion]);

        if ($verbose) {
            echo "    schema version: $schemaVersion\n";
        }

        // 6. Register PSR-4 namespace in autoload cache
        updateNamespaceMap($joomlaPath, $verbose);

        echo "  ADDED:  com_livingword (component) — registered with menu, tables, and schema\n";
    } catch (\Exception $e) {
        echo "  ERROR:  com_livingword (component) — registration failed: " . $e->getMessage() . "\n";
    }
}

/**
 * Adds LivingWord PSR-4 namespace entries to Joomla's autoload cache.
 *
 * Joomla generates administrator/cache/autoload_psr4.php via the
 * NamespaceMap plugin. For symlinked dev installs, we inject our
 * entries directly so the component can be loaded without triggering
 * a full cache rebuild.
 *
 * @param   string  $joomlaPath  Path to the Joomla installation
 * @param   bool    $verbose     Show detailed output
 *
 * @return  void
 *
 * @since  5.0.0
 */
function updateNamespaceMap(string $joomlaPath, bool $verbose = false): void
{
    $mapFile = $joomlaPath . '/administrator/cache/autoload_psr4.php';

    $entries = [
        "'CWM\\\\Component\\\\Livingword\\\\Administrator\\\\'" => "[JPATH_ADMINISTRATOR . '/components/com_livingword/src']",
        "'CWM\\\\Component\\\\Livingword\\\\Site\\\\'"          => "[JPATH_SITE . '/components/com_livingword/src']",
    ];

    if (!file_exists($mapFile)) {
        // Create the file from scratch
        $lines = "<?php\ndefined('_JEXEC') or die;\nreturn [\n";
        foreach ($entries as $ns => $path) {
            $lines .= "\t$ns => $path,\n";
        }
        $lines .= "];\n";
        file_put_contents($mapFile, $lines);

        if ($verbose) {
            echo "    namespace map: created with " . \count($entries) . " entries\n";
        }

        return;
    }

    $content = file_get_contents($mapFile);

    // Check if already registered
    if (str_contains($content, 'Livingword')) {
        if ($verbose) {
            echo "    namespace map: already registered\n";
        }

        return;
    }

    // Insert entries before the closing "];"
    $insertBlock = '';
    foreach ($entries as $ns => $path) {
        $insertBlock .= "\t$ns => $path,\n";
    }

    $content = str_replace('];', $insertBlock . '];', $content);
    file_put_contents($mapFile, $content);

    if ($verbose) {
        echo "    namespace map: added " . \count($entries) . " entries\n";
    }
}

/**
 * Builds the component package (ZIP file).
 *
 * @param   bool  $verbose  If true, lists each file added to the package.
 *
 * @return void
 * @throws Exception If ZIP creation fails.
 * @since 5.0.0
 */
function doBuild(bool $verbose = false): void
{
    // Get version from livingword.xml
    $xmlVersion = '5.0.0';
    if (file_exists(BASE_DIR . '/livingword.xml')) {
        $xml = simplexml_load_string(file_get_contents(BASE_DIR . '/livingword.xml'));
        if ($xml && isset($xml->version)) {
            $xmlVersion = (string) $xml->version;
        }
    }

    $dateVersion = date('Ymd');

    if (stream_isatty(STDIN)) {
        echo "\nSelect version to build:\n";
        echo "  [1] XML Version ($xmlVersion) - Default\n";
        echo "  [2] Date Version ($dateVersion)\n";
        echo "  [3] Custom Version\n";

        $choice = ask('Enter choice [1-3]', '1', 10);

        switch ($choice) {
            case '2':
                $version = $dateVersion;
                break;
            case '3':
                $version = ask('Enter custom version');
                break;
            case '1':
            default:
                $version = $xmlVersion;
                break;
        }
    } else {
        echo "Non-interactive mode detected. Using XML version: $xmlVersion\n";
        $version = $xmlVersion;
    }

    echo "\nPackaging LivingWord v$version...\n";

    $zipFile = BUILD_DIR . "/com_livingword-$version.zip";

    if (file_exists($zipFile)) {
        unlink($zipFile);
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
        throw new \RuntimeException("Cannot open <$zipFile>");
    }

    $resolvedBase = realpath(BASE_DIR);

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($resolvedBase, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    $excludes = [
        'build.xml', 'build.properties', 'build.dist.properties', 'phpunit.xml', 'phpunit.xml.bak',
        '.php-cs-fixer.dist.php', 'CLAUDE.md', '.editorconfig', '_config.yml',
        '.git', '.vscode', '.idea', '.DS_Store', 'node_modules', 'composer.json', 'composer.lock',
        'package.json', 'package-lock.json', 'build', 'tests',
        // Exclude Composer vendor (dev-only)
        'libraries/vendor',
    ];

    $excludeExts = ['map'];

    $includes    = ['admin/', 'media/', 'mod_livingword/', 'plg_task_livingword/', 'site/'];
    $includeExts = ['php', 'xml', 'txt', 'md', 'ini', 'json', 'css', 'js'];

    $fileCount = 0;
    foreach ($files as $name => $file) {
        if ($file->isDir()) {
            continue;
        }

        $filePath     = $file->getRealPath();
        $relativePath = str_replace('\\', '/', substr($filePath, \strlen($resolvedBase) + 1));

        $excludeFile = false;
        foreach ($excludes as $exclude) {
            $cleanExclude = rtrim($exclude, '/');

            if ($relativePath === $cleanExclude) {
                $excludeFile = true;
                break;
            }

            if (str_starts_with($relativePath, $cleanExclude . '/')) {
                $excludeFile = true;
                break;
            }

            if (str_contains($relativePath, '/' . $cleanExclude . '/')) {
                $excludeFile = true;
                break;
            }

            if (str_ends_with($relativePath, '/' . $cleanExclude)) {
                $excludeFile = true;
                break;
            }
        }

        if (!$excludeFile) {
            $ext = pathinfo($relativePath, PATHINFO_EXTENSION);
            if (\in_array($ext, $excludeExts, true)) {
                $excludeFile = true;
            }
        }

        if (!$excludeFile && str_contains($relativePath, '/vendor/')) {
            $basename  = basename($relativePath);
            $upperBase = strtoupper(pathinfo($basename, PATHINFO_FILENAME));

            if ($basename === 'installed.json' || $basename === 'installed.php') {
                $excludeFile = true;
            }

            if (\in_array($upperBase, ['README', 'CHANGELOG', 'BACKERS', 'AUTHORS', 'CONTRIBUTING', 'UPGRADE', 'SECURITY'], true)) {
                $excludeFile = true;
            }

            if ($upperBase === 'LICENSE' || $upperBase === 'COPYING') {
                $excludeFile = true;
            }
        }

        if ($excludeFile) {
            continue;
        }

        $shouldInclude = false;
        foreach ($includes as $include) {
            if (str_starts_with($relativePath, $include)) {
                $shouldInclude = true;
                break;
            }
        }
        if (!$shouldInclude) {
            $ext = pathinfo($relativePath, PATHINFO_EXTENSION);
            if (\in_array($ext, $includeExts, true) && !str_contains($relativePath, '/')) {
                $shouldInclude = true;
            }
        }

        if ($shouldInclude) {
            $zip->addFile($filePath, $relativePath);
            $fileCount++;
            if ($verbose) {
                echo "  + $relativePath\n";
            }
        }
    }

    $zip->close();

    echo "\nBuild complete: com_livingword-$version.zip ($fileCount files)\n";
    echo "Location: $zipFile\n";
}

/**
 * Downloads and installs a specific version of Joomla.
 *
 * @return void
 * @throws Exception
 * @since 5.0.0
 */
function doInstallJoomla(): void
{
    $props          = getProperties();
    $defaultVersion = $props['joomla.version'] ?? '5.4.2';
    $joomlaPaths    = getJoomlaPaths($props);

    if (\count($joomlaPaths) === 0) {
        $joomlaPaths = [rtrim($props['builder.joomla_path'] ?? '', '/') . '/' . trim($props['builder.joomla_dir'] ?? '', '/')];
        $joomlaPaths = [rtrim($joomlaPaths[0], '/')];
    }

    foreach ($joomlaPaths as $installPath) {
        echo "\nInstall target: $installPath\n";

        $version = ask("  Joomla version", $defaultVersion);

        if (is_dir($installPath)) {
            $reinstall = ask("  Directory exists. Remove and reinstall? (y/n)", 'n');
            if (strtolower($reinstall) !== 'y') {
                echo "  Skipped.\n";
                continue;
            }
            if (PHP_OS_FAMILY === 'Windows') {
                exec('rmdir /s /q ' . escapeshellarg($installPath));
            } else {
                exec('rm -rf ' . escapeshellarg($installPath));
            }
        }

        if (!is_dir($installPath) && !mkdir($installPath, 0777, true) && !is_dir($installPath)) {
            echo "  ERROR: Failed to create directory.\n";
            continue;
        }

        $url     = "https://github.com/joomla/joomla-cms/releases/download/$version/Joomla_$version-Stable-Full_Package.zip";
        $zipFile = BUILD_DIR . "/joomla-$version.zip";

        echo "  Downloading Joomla $version...";
        copy($url, $zipFile);

        $zip = new ZipArchive();
        if ($zip->open($zipFile) === true) {
            $zip->extractTo($installPath);
            $zip->close();
            echo " installed.\n";
        } else {
            echo " FAILED to extract.\n";
        }
        if (file_exists($zipFile)) {
            unlink($zipFile);
        }
    }
}

/**
 * Removes all symbolic links created by the link command.
 *
 * @param   bool  $verbose  If true, prints each removed path.
 *
 * @return void
 * @since 5.0.0
 */
function doClean(bool $verbose = false): void
{
    echo "Cleaning up development state...\n";

    if (file_exists(PROPERTIES_FILE)) {
        try {
            $props       = getProperties();
            $joomlaPaths = getJoomlaPaths($props);

            foreach ($joomlaPaths as $joomlaPath) {
                if (!is_dir($joomlaPath)) {
                    continue;
                }

                $count = 0;
                foreach (getExternalLinks($joomlaPath) as $link) {
                    if (is_link($link)) {
                        unlink($link);
                        $count++;
                        if ($verbose) {
                            echo "  Removed: $link\n";
                        }
                    }
                }
                echo "\nCleaned: $joomlaPath ($count symlinks)\n";
            }
        } catch (Exception $e) {
            // Ignore if properties can't be read
        }
    }

    echo "\nClean complete.\n";
}

/**
 * Fetches and displays the latest available Joomla version from GitHub.
 *
 * @return void
 * @throws Exception If the GitHub API request fails.
 * @since 5.0.0
 */
function doJoomlaLatest(): void
{
    echo "Fetching latest Joomla version from GitHub...\n";

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: LivingWord-Build-Tool',
        ],
    ]);

    $json = @file_get_contents('https://api.github.com/repos/joomla/joomla-cms/releases/latest', false, $context);

    if ($json === false) {
        throw new \RuntimeException('Failed to fetch from GitHub API');
    }

    $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    if (!$data || !isset($data['tag_name'])) {
        throw new \RuntimeException('Invalid response from GitHub API');
    }

    $version   = $data['tag_name'];
    $published = $data['published_at'] ?? 'unknown';

    echo "\nLatest Joomla Version: $version\n";
    echo "Published: $published\n";
    echo "\nTo install: composer joomla-install\n";
}

/**
 * Checks all PHP files in the project for syntax errors.
 *
 * @param   bool  $verbose  If true, prints each file as it is checked.
 *
 * @return void
 * @since 5.0.0
 */
function doLintSyntax(bool $verbose = false): void
{
    echo "Checking PHP syntax...\n";

    $directories = ['admin/src', 'site/src', 'mod_livingword', 'plg_task_livingword'];
    $errors      = [];
    $fileCount   = 0;

    foreach ($directories as $dir) {
        $path = BASE_DIR . '/' . $dir;
        if (!is_dir($path)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $filePath     = $file->getRealPath();
            $relativePath = str_replace(BASE_DIR . '/', '', $filePath);
            $fileCount++;

            if ($verbose) {
                echo "  $relativePath\n";
            }

            $output    = [];
            $returnVar = 0;
            exec('php -l ' . escapeshellarg($filePath) . ' 2>&1', $output, $returnVar);

            if ($returnVar !== 0) {
                $errors[] = [
                    'file'  => $relativePath,
                    'error' => implode("\n", $output),
                ];
            }
        }
    }

    if (\count($errors) > 0) {
        echo "\nSyntax errors found in $fileCount files checked:\n";
        echo str_repeat('-', 60) . "\n";
        foreach ($errors as $error) {
            echo "File: {$error['file']}\n";
            echo "{$error['error']}\n\n";
        }
        exit(1);
    }

    echo "No syntax errors in $fileCount files.\n";
}
// phpcs:enable PSR1.Files.SideEffects
