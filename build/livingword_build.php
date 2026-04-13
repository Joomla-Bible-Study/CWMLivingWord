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
const VENDOR_DIR      = BUILD_DIR . '/vendor';
const PKG_MANIFEST    = BUILD_DIR . '/pkg_livingword.xml';

require_once __DIR__ . '/fetch_dependencies.php';

$command        = $argv[1] ?? 'help';
$verbose        = \in_array('--verbose', $argv, true) || \in_array('-v', $argv, true);
$localScripture = \in_array('--local-scripture', $argv, true);

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
            doBuild($verbose, $localScripture);
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
 * Read the admin menu structure from livingword.xml manifest.
 *
 * Parses the <administration><menu> and <submenu> elements to build
 * the menu data array used by registerComponent and syncAdminMenus.
 *
 * @return  array  Array of menu item definitions
 *
 * @since   5.7.0
 */
function getAdminMenuData(): array
{
    $xmlFile = BASE_DIR . '/livingword.xml';

    if (!file_exists($xmlFile)) {
        return [];
    }

    $xml = simplexml_load_string(file_get_contents($xmlFile));

    if (!$xml || !isset($xml->administration->menu)) {
        return [];
    }

    $menuData = [];

    // Root menu item
    $rootMenu   = $xml->administration->menu;
    $rootImg    = (string) ($rootMenu['img'] ?? 'class:component');
    $menuData[] = [
        'title'  => trim((string) $rootMenu),
        'link'   => 'index.php?option=com_livingword',
        'alias'  => 'com-livingword',
        'level'  => 1,
        'parent' => 1,
        'img'    => $rootImg,
    ];

    // Submenu items
    if (isset($xml->administration->submenu->menu)) {
        foreach ($xml->administration->submenu->menu as $sub) {
            $link  = str_replace('&amp;', '&', (string) ($sub['link'] ?? ''));
            $view  = (string) ($sub['view'] ?? '');
            $title = trim((string) $sub);
            $alias = 'com-livingword-' . str_replace('cwm', '', $view);

            $menuData[] = [
                'title' => $title,
                'link'  => 'index.php?' . $link,
                'alias' => $alias,
                'level' => 2,
            ];
        }
    }

    return $menuData;
}

/**
 * Sync admin menu items from manifest for an existing component.
 *
 * Adds any missing submenu items that are defined in livingword.xml
 * but don't yet exist in the #__menu table.
 *
 * @param   PDO     $pdo          Database connection
 * @param   string  $prefix       Table prefix
 * @param   int     $extensionId  The component's extension_id
 * @param   bool    $verbose      Show detailed output
 *
 * @return  void
 *
 * @since   5.7.0
 */
function syncAdminMenus(PDO $pdo, string $prefix, int $extensionId, bool $verbose = false): void
{
    $menuData = getAdminMenuData();

    if (empty($menuData)) {
        return;
    }

    // Find existing parent menu item
    $stmt = $pdo->prepare(
        "SELECT id FROM {$prefix}menu WHERE menutype = 'main' AND link = ? AND client_id = 1 LIMIT 1"
    );
    $stmt->execute(['index.php?option=com_livingword']);
    $parentMenuId = (int) $stmt->fetchColumn();

    if ($parentMenuId === 0) {
        return;
    }

    $added = 0;

    // Collect submenu items in manifest order
    $subMenus = [];

    foreach ($menuData as $item) {
        if ($item['level'] === 1) {
            continue;
        }

        $subMenus[] = $item;
    }

    // Get existing submenu items
    $stmt = $pdo->prepare(
        "SELECT id, link FROM {$prefix}menu WHERE menutype = 'main' AND parent_id = ? AND client_id = 1"
    );
    $stmt->execute([$parentMenuId]);
    $existingMenus  = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $existingByLink = array_column($existingMenus, 'id', 'link');

    // Manifest links for cleanup
    $manifestLinks = array_map(fn ($item) => $item['link'], $subMenus);

    // Remove stale menu items no longer in manifest
    foreach ($existingMenus as $existing) {
        if (!\in_array($existing['link'], $manifestLinks, true)) {
            $pdo->exec("DELETE FROM {$prefix}menu WHERE id = " . (int) $existing['id']);

            if ($verbose) {
                echo "    menu removed: {$existing['link']}\n";
            }
        }
    }

    // Insert missing items
    foreach ($subMenus as $item) {
        if (isset($existingByLink[$item['link']])) {
            // Update title to match manifest
            $pdo->prepare(
                "UPDATE {$prefix}menu SET title = ? WHERE id = ?"
            )->execute([$item['title'], (int) $existingByLink[$item['link']]]);

            continue;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO {$prefix}menu "
            . "(menutype, title, alias, link, type, published, parent_id, level, component_id, client_id, img, path, lft, rgt, access, params, language) "
            . "VALUES ('main', ?, ?, ?, 'component', 1, ?, 2, ?, 1, '', ?, 0, 0, 1, '', '*')"
        );
        $stmt->execute([
            $item['title'],
            $item['alias'],
            $item['link'],
            $parentMenuId,
            $extensionId,
            'com-livingword/' . $item['alias'],
        ]);

        $added++;
    }

    // Rebuild lft/rgt for submenu items in manifest order
    $stmt = $pdo->prepare(
        "SELECT lft FROM {$prefix}menu WHERE id = ?"
    );
    $stmt->execute([$parentMenuId]);
    $parentLft = (int) $stmt->fetchColumn();

    $lft = $parentLft + 1;

    foreach ($subMenus as $item) {
        $stmt = $pdo->prepare(
            "SELECT id FROM {$prefix}menu WHERE menutype = 'main' AND link = ? AND client_id = 1 LIMIT 1"
        );
        $stmt->execute([$item['link']]);
        $menuId = (int) $stmt->fetchColumn();

        if ($menuId) {
            $rgt = $lft + 1;
            $pdo->prepare("UPDATE {$prefix}menu SET lft = ?, rgt = ? WHERE id = ?")->execute([$lft, $rgt, $menuId]);
            $lft = $rgt + 1;
        }
    }

    // Update parent rgt to encompass all children
    $pdo->prepare("UPDATE {$prefix}menu SET rgt = ? WHERE id = ?")->execute([$lft, $parentMenuId]);

    if ($added > 0 && $verbose) {
        echo "    menu items: $added added\n";
    }
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
        "$base/livingword.xml"                              => "$base/admin/livingword.xml",
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
 * Build the manifest_cache JSON string from an extension's XML manifest.
 *
 * @param   string  $manifestPath  Absolute path to the XML manifest file
 * @param   string  $type          Extension type (component, module, plugin)
 * @param   string  $defaultName   Fallback name if XML has none
 *
 * @return  string  JSON-encoded manifest cache, or '{}' if manifest unreadable
 *
 * @since   5.0.0
 */
function buildManifestCache(string $manifestPath, string $type, string $defaultName): string
{
    if (!file_exists($manifestPath)) {
        return '{}';
    }

    $xml = simplexml_load_string(file_get_contents($manifestPath));

    if (!$xml) {
        return '{}';
    }

    return json_encode([
        'name'         => (string) ($xml->name ?? $defaultName),
        'type'         => $type,
        'creationDate' => (string) ($xml->creationDate ?? ''),
        'author'       => (string) ($xml->author ?? ''),
        'copyright'    => (string) ($xml->copyright ?? ''),
        'authorEmail'  => (string) ($xml->authorEmail ?? ''),
        'authorUrl'    => (string) ($xml->authorUrl ?? ''),
        'version'      => (string) ($xml->version ?? '5.0.0'),
        'description'  => (string) ($xml->description ?? ''),
        'group'        => '',
    ]);
}

/**
 * Insert an extension row into #__extensions.
 *
 * Works for any extension type (component, module, plugin).
 *
 * @param   PDO     $pdo            Database connection
 * @param   string  $prefix         Table prefix
 * @param   bool    $hasNamespace   Whether the extensions table has a namespace column
 * @param   array   $ext            Extension definition from the $expected array
 * @param   string  $manifestCache  JSON manifest_cache string
 *
 * @return  int  The new extension_id
 *
 * @since   5.0.0
 */
function insertExtension(PDO $pdo, string $prefix, bool $hasNamespace, array $ext, string $manifestCache): int
{
    $columns = 'name, type, element, folder, client_id, enabled, access, locked, state, manifest_cache, params, custom_data';
    $values  = '?, ?, ?, ?, ?, ?, 1, 0, 0, ?, ?, ?';
    $params  = [
        $ext['name'],
        $ext['type'],
        $ext['element'],
        $ext['folder'],
        $ext['client_id'],
        $ext['enabled'],
        $manifestCache,
        '{}',
        '',
    ];

    if ($hasNamespace && !empty($ext['namespace'])) {
        $columns .= ', namespace';
        $values  .= ', ?';
        $params[] = str_replace('\\', '\\\\', $ext['namespace']);
    }

    $stmt = $pdo->prepare("INSERT INTO {$prefix}extensions ($columns) VALUES ($values)");
    $stmt->execute($params);

    return (int) $pdo->lastInsertId();
}

/**
 * Run CREATE TABLE IF NOT EXISTS statements from the install SQL file.
 *
 * Safe to call repeatedly — only creates missing tables.
 *
 * @param   PDO     $pdo      Database connection
 * @param   string  $prefix   Table prefix
 * @param   bool    $verbose  Show detailed output
 *
 * @return  void
 *
 * @since   5.0.0
 */
function runInstallSql(PDO $pdo, string $prefix, bool $verbose = false): void
{
    $sqlFile = BASE_DIR . '/admin/sql/install.mysql.utf8.sql';

    if (!file_exists($sqlFile)) {
        return;
    }

    $sqlContent = file_get_contents($sqlFile);
    preg_match_all('/CREATE TABLE IF NOT EXISTS[^;]+;/si', $sqlContent, $matches);

    $count = 0;

    foreach ($matches[0] as $createSql) {
        $createSql = str_replace('#__', $prefix, $createSql);

        try {
            $pdo->exec($createSql);
            $count++;
        } catch (PDOException) {
            // Table already exists or other non-fatal issue
        }
    }

    if ($verbose && $count > 0) {
        echo "    tables: $count CREATE statements executed\n";
    }
}

/**
 * Ensure a site module has an instance row in #__modules and a menu assignment.
 *
 * Joomla requires a row in #__modules to make a module assignable to template
 * positions, and a row in #__modules_menu (menuid=0) to show on all pages.
 * Without these, the module appears in Extensions but can't actually be used.
 *
 * Safe to call repeatedly — only creates rows if missing.
 *
 * @param   PDO     $pdo      Database connection
 * @param   string  $prefix   Table prefix
 * @param   string  $element  Module element name (e.g. 'mod_livingword')
 * @param   bool    $verbose  Show detailed output
 *
 * @return  void
 *
 * @since   5.0.0
 */
function ensureModuleInstance(PDO $pdo, string $prefix, string $element, bool $verbose = false): void
{
    // Check if a module instance already exists
    $stmt = $pdo->prepare(
        "SELECT id FROM {$prefix}modules WHERE module = ? AND client_id = 0"
    );
    $stmt->execute([$element]);
    $moduleId = $stmt->fetchColumn();

    if ($moduleId) {
        if ($verbose) {
            echo "    module instance: already exists (ID $moduleId)\n";
        }
    } else {
        // Create an unpublished module instance — admin will set position and publish
        $stmt = $pdo->prepare(
            "INSERT INTO {$prefix}modules "
            . "(title, module, position, published, access, ordering, client_id, language, params, note) "
            . "VALUES (?, ?, '', 0, 1, 0, 0, '*', '{}', 'Created by composer verify — set position and publish to use.')"
        );
        $stmt->execute(['LivingWord Daily Reading', $element]);
        $moduleId = (int) $pdo->lastInsertId();

        if ($verbose) {
            echo "    module instance: created (ID $moduleId, unpublished)\n";
        }
    }

    // Ensure menu assignment exists (menuid=0 means "all pages")
    $stmt = $pdo->prepare(
        "SELECT moduleid FROM {$prefix}modules_menu WHERE moduleid = ?"
    );
    $stmt->execute([$moduleId]);

    if (!$stmt->fetchColumn()) {
        $pdo->prepare(
            "INSERT INTO {$prefix}modules_menu (moduleid, menuid) VALUES (?, 0)"
        )->execute([$moduleId]);

        if ($verbose) {
            echo "    module menu: assigned to all pages\n";
        }
    }
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
        [
            'type' => 'module', 'element' => 'mod_livingword', 'name' => 'mod_livingword',
            'folder' => '', 'enabled' => 1, 'locked' => 0, 'client_id' => 0,
            'manifest' => 'mod_livingword/mod_livingword.xml',
            'namespace' => 'CWM\\Module\\Livingword\\Site',
        ],
        [
            'type' => 'plugin', 'element' => 'livingword', 'name' => 'plg_task_livingword',
            'folder' => 'task', 'enabled' => 1, 'locked' => 0, 'client_id' => 0,
            'manifest' => 'plg_task_livingword/livingword.xml',
            'namespace' => 'CWM\\Plugin\\Task\\Livingword',
        ],
        [
            'type' => 'component', 'element' => 'com_livingword', 'name' => 'com_livingword',
            'folder' => '', 'enabled' => 1, 'locked' => 0, 'client_id' => 1,
            'manifest' => 'livingword.xml',
            'namespace' => 'CWM\\Component\\Livingword',
        ],
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

            $manifestPath = BASE_DIR . '/' . $ext['manifest'];
            $mc           = buildManifestCache($manifestPath, $type, $name);

            // Look up existing extension row
            $sql    = "SELECT extension_id, enabled, locked, state FROM {$prefix}extensions WHERE type = ? AND element = ?";
            $params = [$type, $element];

            if ($type === 'plugin' && $folder !== '') {
                $sql .= ' AND folder = ?';
                $params[] = $folder;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // --- Extension exists: check for needed updates ---
                $needsUpdate  = false;
                $updates      = [];
                $updateParams = [];

                if ((int) $row['enabled'] !== $ext['enabled']) {
                    $updates[]   = "enabled = {$ext['enabled']}";
                    $needsUpdate = true;
                }

                if ($ext['locked'] && (int) $row['locked'] !== 1) {
                    $updates[]   = 'locked = 1';
                    $needsUpdate = true;
                }

                // Fix state=-1 (discovered but not installed) → state=0 (installed)
                if ((int) ($row['state'] ?? 0) === -1) {
                    $updates[]   = 'state = 0';
                    $needsUpdate = true;
                }

                // Module-specific: ensure #__modules instance and menu assignment exist
                if ($type === 'module') {
                    ensureModuleInstance($pdo, $prefix, $element, $verbose);
                }

                // Always refresh manifest_cache
                if ($mc !== '{}') {
                    $updates[]      = 'manifest_cache = ?';
                    $updateParams[] = $mc;
                    $needsUpdate    = true;
                }

                // Component-specific: sync menus and ensure tables exist
                if ($type === 'component') {
                    syncAdminMenus($pdo, $prefix, (int) $row['extension_id'], $verbose);
                    runInstallSql($pdo, $prefix, $verbose);
                }

                if ($needsUpdate) {
                    $updateSql = "UPDATE {$prefix}extensions SET " . implode(', ', $updates)
                        . " WHERE extension_id = " . (int) $row['extension_id'];
                    $stmt = $pdo->prepare($updateSql);
                    $stmt->execute($updateParams);
                    echo "  FIXED:  $name ($type) — updated " . implode(', ', array_map(fn ($u) => explode(' =', $u)[0], $updates)) . "\n";
                    $fixed++;
                } else {
                    if ($verbose) {
                        echo "  OK:     $name ($type)\n";
                    }
                    $ok++;
                }
            } else {
                // --- Extension missing: register it ---
                if ($type === 'component') {
                    registerComponent($pdo, $prefix, $hasNamespace, $joomlaPath, $ext, $mc, $verbose);
                } else {
                    insertExtension($pdo, $prefix, $hasNamespace, $ext, $mc);

                    if ($type === 'module') {
                        ensureModuleInstance($pdo, $prefix, $element, $verbose);
                    }

                    $label = $type === 'plugin' ? "plugin/$folder" : $type;
                    echo "  ADDED:  $name ($label)\n";
                }

                $fixed++;
            }
        }

        // Always ensure the namespace map is up to date
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
 * @param   PDO     $pdo            Database connection
 * @param   string  $prefix         Table prefix
 * @param   bool    $hasNamespace   Whether the extensions table has a namespace column
 * @param   string  $joomlaPath     Path to the Joomla installation
 * @param   array   $ext            Extension definition from the $expected array
 * @param   string  $manifestCache  Pre-built manifest cache JSON
 * @param   bool    $verbose        Show detailed output
 *
 * @return  void
 *
 * @since  5.0.0
 */
function registerComponent(PDO $pdo, string $prefix, bool $hasNamespace, string $joomlaPath, array $ext, string $manifestCache, bool $verbose = false): void
{
    try {
        // 1. Insert into #__extensions (uses shared helper)
        $extensionId = insertExtension($pdo, $prefix, $hasNamespace, $ext, $manifestCache);

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
        $menuData = getAdminMenuData();

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

        // 4. Run install SQL to create tables
        runInstallSql($pdo, $prefix, $verbose);

        // 5. Insert schema version
        $manifestPath  = BASE_DIR . '/' . $ext['manifest'];
        $schemaVersion = '5.0.0';

        if (file_exists($manifestPath)) {
            $xml = simplexml_load_string(file_get_contents($manifestPath));

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
        "'CWM\\\\Module\\\\Livingword\\\\Site\\\\'"             => "[JPATH_SITE . '/modules/mod_livingword/src']",
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
    $added   = 0;

    // Check each entry individually and add any that are missing
    foreach ($entries as $ns => $path) {
        // Strip quotes from key for the contains check
        $nsCheck = trim($ns, "'");

        if (str_contains($content, $nsCheck)) {
            continue;
        }

        $insertLine = "\t$ns => $path,\n";
        $content    = str_replace('];', $insertLine . '];', $content);
        $added++;
    }

    if ($added === 0) {
        if ($verbose) {
            echo "    namespace map: already registered\n";
        }

        return;
    }

    file_put_contents($mapFile, $content);

    if ($verbose) {
        echo "    namespace map: added $added entries\n";
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
function doBuild(bool $verbose = false, bool $localScripture = false): void
{
    $version = resolveBuildVersion();

    echo "\nPackaging LivingWord v$version...\n";

    if (!is_dir(VENDOR_DIR) && !mkdir(VENDOR_DIR, 0755, true) && !is_dir(VENDOR_DIR)) {
        throw new \RuntimeException('Cannot create vendor directory: ' . VENDOR_DIR);
    }

    if ($localScripture) {
        echo "\n[1/5] Building scripture dependencies from local sibling repos...\n";
        $scripture = buildLocalScriptureDependencies($verbose);
        echo "  lib_cwmscripture @ local ../lib_cwmscripture (v{$scripture['version']})\n";
    } else {
        echo "\n[1/5] Fetching scripture dependencies from GitHub...\n";
        $scripture = fetchScriptureDependencies(VENDOR_DIR, $verbose);
        echo "  lib_cwmscripture @ v{$scripture['version']} (from " . SCRIPTURE_REPO . ")\n";
    }

    echo "\n[2/5] Building com_livingword.zip...\n";
    $componentZip = buildComponentZip($verbose);

    echo "\n[3/5] Building mod_livingword.zip...\n";
    $moduleZip = buildSubExtensionZip(
        BASE_DIR . '/mod_livingword',
        VENDOR_DIR . '/mod_livingword.zip',
        $verbose
    );

    echo "\n[4/5] Building plg_task_livingword.zip...\n";
    $taskPluginZip = buildSubExtensionZip(
        BASE_DIR . '/plg_task_livingword',
        VENDOR_DIR . '/plg_task_livingword.zip',
        $verbose
    );

    echo "\n[5/5] Bundling pkg_livingword-$version.zip...\n";
    $pkgZip = BUILD_DIR . "/pkg_livingword-$version.zip";

    bundlePackage(
        $version,
        [
            'lib_cwmscripture.zip'           => $scripture['zips']['lib_cwmscripture.zip'],
            'com_livingword.zip'             => $componentZip,
            'mod_livingword.zip'             => $moduleZip,
            'plg_task_livingword.zip'        => $taskPluginZip,
            'plg_task_cwmscripture.zip'      => $scripture['zips']['plg_task_cwmscripture.zip'],
            'plg_content_scripturelinks.zip' => $scripture['zips']['plg_content_scripturelinks.zip'],
        ],
        $pkgZip
    );

    echo "\nBuild complete: pkg_livingword-$version.zip\n";
    echo "Location: $pkgZip\n";
    echo "Bundled scripture library: v{$scripture['version']}\n";
}

/**
 * Build the scripture library and content plugin zips from local sibling
 * repositories instead of fetching the latest release from GitHub.
 *
 * Used for testing unreleased library changes end-to-end before cutting a
 * real release.  Resolves lib_cwmscripture from ../lib_cwmscripture and
 * plg_content_scripturelinks from ../CWMScriptureLinks.
 *
 * @return  array{version: string, zips: array<string, string>}
 *
 * @since 5.1.0
 */
function buildLocalScriptureDependencies(bool $verbose): array
{
    $libSource    = \dirname(BASE_DIR) . '/lib_cwmscripture';
    $pluginSource = \dirname(BASE_DIR) . '/CWMScriptureLinks';

    if ($libSource === false || !is_dir($libSource)) {
        throw new \RuntimeException('Local lib_cwmscripture not found at ../lib_cwmscripture');
    }

    if ($pluginSource === false || !is_dir($pluginSource)) {
        throw new \RuntimeException('Local CWMScriptureLinks not found at ../CWMScriptureLinks');
    }

    // Delegate to each repo's own build script — that's the authoritative
    // way to produce a release zip with the correct layout and excludes.
    runSubBuild($libSource, 'build/build-package.php', [], $verbose);
    runSubBuild($pluginSource, 'build/build-package.php', ['--plugin-only'], $verbose);

    $libDist    = $libSource . '/build/dist';
    $pluginDist = $pluginSource . '/build/dist';

    $libCandidates = glob($libDist . '/lib_cwmscripture-*.zip') ?: [];

    if (empty($libCandidates)) {
        throw new \RuntimeException("No lib_cwmscripture-*.zip produced in $libDist");
    }

    // Pick the most recently built candidate.
    usort($libCandidates, static fn ($a, $b) => filemtime($b) <=> filemtime($a));
    $libZipSource    = $libCandidates[0];
    $pluginZipSource = $pluginDist . '/plg_content_scripturelinks.zip';

    if (!file_exists($pluginZipSource)) {
        throw new \RuntimeException("plg_content_scripturelinks.zip not produced in $pluginDist");
    }

    $libZip        = VENDOR_DIR . '/lib_cwmscripture.zip';
    $pluginZip     = VENDOR_DIR . '/plg_content_scripturelinks.zip';
    $taskPluginZip = VENDOR_DIR . '/plg_task_cwmscripture.zip';

    $taskPluginSource = $pluginDist . '/plg_task_cwmscripture.zip';

    if (!file_exists($taskPluginSource)) {
        throw new \RuntimeException("plg_task_cwmscripture.zip not produced in $pluginDist");
    }

    copy($libZipSource, $libZip);
    copy($pluginZipSource, $pluginZip);
    copy($taskPluginSource, $taskPluginZip);

    $version = '0.0.0';
    $xmlPath = $libSource . '/cwmscripture.xml';

    if (file_exists($xmlPath)) {
        $xml = simplexml_load_string(file_get_contents($xmlPath));

        if ($xml && isset($xml->version)) {
            $version = (string) $xml->version;
        }
    }

    return [
        'version' => $version,
        'zips'    => [
            'lib_cwmscripture.zip'           => $libZip,
            'plg_content_scripturelinks.zip' => $pluginZip,
            'plg_task_cwmscripture.zip'      => $taskPluginZip,
        ],
    ];
}

/**
 * Run another repo's build script from its own directory, streaming output.
 *
 * @since 5.1.0
 */
function runSubBuild(string $repoDir, string $scriptRelative, array $args, bool $verbose): void
{
    $script = $repoDir . '/' . $scriptRelative;

    if (!file_exists($script)) {
        throw new \RuntimeException("Build script not found: $script");
    }

    $cmd = 'cd ' . escapeshellarg($repoDir)
        . ' && php ' . escapeshellarg($scriptRelative);

    foreach ($args as $arg) {
        $cmd .= ' ' . escapeshellarg($arg);
    }

    $cmd .= ' 2>&1';

    if ($verbose) {
        echo "  $ $cmd\n";
    }

    $output = [];
    $status = 0;
    exec($cmd, $output, $status);

    if ($status !== 0) {
        echo implode("\n", $output) . "\n";
        throw new \RuntimeException("Sub-build failed in $repoDir (exit $status)");
    }

    if ($verbose) {
        foreach ($output as $line) {
            echo "    $line\n";
        }
    }
}

/**
 * Resolve the version to use for this build (interactive or from manifest).
 *
 * @since 5.1.0
 */
function resolveBuildVersion(): string
{
    $xmlVersion = '5.0.0';

    if (file_exists(BASE_DIR . '/livingword.xml')) {
        $xml = simplexml_load_string(file_get_contents(BASE_DIR . '/livingword.xml'));

        if ($xml && isset($xml->version)) {
            $xmlVersion = (string) $xml->version;
        }
    }

    $dateVersion = date('Ymd');

    if (!stream_isatty(STDIN)) {
        echo "Non-interactive mode detected. Using XML version: $xmlVersion\n";

        return $xmlVersion;
    }

    echo "\nSelect version to build:\n";
    echo "  [1] XML Version ($xmlVersion) - Default\n";
    echo "  [2] Date Version ($dateVersion)\n";
    echo "  [3] Custom Version\n";

    $choice = ask('Enter choice [1-3]', '1', 10);

    return match ($choice) {
        '2'     => $dateVersion,
        '3'     => ask('Enter custom version'),
        default => $xmlVersion,
    };
}

/**
 * Build com_livingword.zip — the component itself plus its media folder.
 *
 * The zip layout mirrors what Joomla's component installer expects: the
 * livingword.xml manifest at the root, with admin/, site/, media/ and
 * script.php as siblings.
 *
 * @since 5.1.0
 */
function buildComponentZip(bool $verbose): string
{
    $destZip = VENDOR_DIR . '/com_livingword.zip';

    if (file_exists($destZip)) {
        unlink($destZip);
    }

    $zip = new ZipArchive();

    if ($zip->open($destZip, ZipArchive::CREATE) !== true) {
        throw new \RuntimeException("Cannot open $destZip");
    }

    $rootFiles = ['livingword.xml', 'script.php', 'LICENSE', 'README.md'];

    foreach ($rootFiles as $file) {
        $path = BASE_DIR . '/' . $file;

        if (file_exists($path)) {
            $zip->addFile($path, $file);

            if ($verbose) {
                echo "  + $file\n";
            }
        }
    }

    foreach (['admin', 'site', 'media'] as $folder) {
        addDirToZip($zip, BASE_DIR . '/' . $folder, $folder, $verbose);
    }

    $zip->close();

    return $destZip;
}

/**
 * Build a sub-extension zip (module or plugin) from a source directory.
 *
 * The directory's contents are copied into the zip root — the manifest XML
 * sits at the top of the zip, matching Joomla's extension installer layout.
 *
 * @since 5.1.0
 */
function buildSubExtensionZip(string $sourceDir, string $destZip, bool $verbose): string
{
    if (!is_dir($sourceDir)) {
        throw new \RuntimeException("Source directory not found: $sourceDir");
    }

    if (file_exists($destZip)) {
        unlink($destZip);
    }

    $zip = new ZipArchive();

    if ($zip->open($destZip, ZipArchive::CREATE) !== true) {
        throw new \RuntimeException("Cannot open $destZip");
    }

    addDirToZip($zip, $sourceDir, '', $verbose);

    $zip->close();

    return $destZip;
}

/**
 * Recursively add a directory's contents to a zip archive, applying the
 * standard build exclusion rules (no .git, .idea, node_modules, maps, etc.).
 *
 * @since 5.1.0
 */
function addDirToZip(ZipArchive $zip, string $sourceDir, string $zipPrefix, bool $verbose): void
{
    $resolved = realpath($sourceDir);

    if ($resolved === false) {
        throw new \RuntimeException("Cannot resolve $sourceDir");
    }

    $excludeNames = [
        '.git', '.vscode', '.idea', '.DS_Store', 'node_modules',
        'build.properties', 'build.dist.properties', 'phpunit.xml',
        '.php-cs-fixer.dist.php', '.editorconfig',
    ];

    $excludeExts = ['map'];

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if ($file->isDir()) {
            continue;
        }

        $filePath     = $file->getRealPath();
        $relativePath = str_replace('\\', '/', substr($filePath, \strlen($resolved) + 1));

        $basename = basename($relativePath);

        if (\in_array($basename, $excludeNames, true)) {
            continue;
        }

        $skip = false;

        foreach ($excludeNames as $name) {
            if (str_contains('/' . $relativePath . '/', '/' . $name . '/')) {
                $skip = true;
                break;
            }
        }

        if ($skip) {
            continue;
        }

        $ext = pathinfo($relativePath, PATHINFO_EXTENSION);

        if (\in_array($ext, $excludeExts, true)) {
            continue;
        }

        $zipPath = $zipPrefix === '' ? $relativePath : $zipPrefix . '/' . $relativePath;
        $zip->addFile($filePath, $zipPath);

        if ($verbose) {
            echo "  + $zipPath\n";
        }
    }
}

/**
 * Bundle sub-extension zips into the final pkg_livingword package.
 *
 * Writes a concrete pkg manifest (with @VERSION@ / @DATE@ substituted)
 * and the listed sub-zips at the root of the package zip.
 *
 * @param   array<string, string>  $subZips  Map of archive filename → source path.
 *
 * @since 5.1.0
 */
function bundlePackage(string $version, array $subZips, string $destZip): void
{
    if (!file_exists(PKG_MANIFEST)) {
        throw new \RuntimeException('Package manifest template not found: ' . PKG_MANIFEST);
    }

    $manifest = file_get_contents(PKG_MANIFEST);
    $manifest = strtr($manifest, [
        '@VERSION@' => $version,
        '@DATE@'    => date('Y-m-d'),
    ]);

    if (file_exists($destZip)) {
        unlink($destZip);
    }

    $zip = new ZipArchive();

    if ($zip->open($destZip, ZipArchive::CREATE) !== true) {
        throw new \RuntimeException("Cannot open $destZip");
    }

    $zip->addFromString('pkg_livingword.xml', $manifest);

    foreach ($subZips as $archiveName => $sourcePath) {
        if (!file_exists($sourcePath)) {
            throw new \RuntimeException("Missing sub-extension zip: $sourcePath");
        }

        $zip->addFile($sourcePath, $archiveName);
    }

    $zip->close();
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
