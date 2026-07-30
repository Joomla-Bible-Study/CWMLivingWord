<?php

/**
 * Tear LivingWord down on every `role = test` install so the next install
 * starts from a genuine clean slate.
 *
 * A repeatable install test must begin from a known state:
 *   - a stale #__extensions row makes Joomla route a fresh install to update(),
 *     so the "clean install" test silently becomes an upgrade test, and
 *   - stale #__livingword_* tables mask CREATE TABLE failures in install.sql.
 *
 * Removes the LivingWord family (package, component, module, task plugin) from
 * #__extensions and the rows that hang off it — #__schemas, #__assets,
 * #__categories, #__content_types, #__contentitem_tag_map, #__update_sites —
 * then drops the nine #__livingword_* tables.
 *
 * The bundled CWM Scripture extensions are left alone by default. The package
 * ships them, but so does Proclaim, and a test site may have both: dropping the
 * shared library out from under a Proclaim install would break it. Pass
 * --with-scripture when you specifically want to exercise the library's own
 * install path.
 *
 * SAFETY: only installs marked `role = test` in build.properties are touched.
 * Never mark a dev or production install as `role = test`.
 *
 * Usage:
 *   php build/reset-testsite.php [--with-scripture]
 *
 * @package  Livingword.Build
 * @since    __DEPLOY_VERSION__
 */

declare(strict_types=1);

use CWM\BuildTools\Dev\PropertiesReader;

$root = \dirname(__DIR__);

require $root . '/libraries/vendor/autoload.php';

$withScripture = \in_array('--with-scripture', $argv ?? [], true);

$reader   = new PropertiesReader($root . '/build.properties');
$installs = $reader->installsFor('test');

if ($installs === []) {
    fwrite(STDERR, "No role=test install in build.properties — nothing to reset.\n");
    fwrite(STDERR, "Add a section with `role = test` to make a site the zip-install target.\n");

    exit(0);
}

/**
 * Read a Joomla install's database credentials out of its configuration.php.
 *
 * @return array{0: string, 1: string, 2: string, 3: string, 4: string}
 */
$readConfig = static function (string $file): array {
    require $file;
    $c = new \JConfig();

    return [$c->host, $c->user, $c->password, $c->db, $c->dbprefix];
};

$failed = 0;

foreach ($installs as $install) {
    echo "=== reset {$install->id} ({$install->path}) ===\n";

    $configFile = $install->path . '/configuration.php';

    if (!is_file($configFile)) {
        fwrite(STDERR, "  configuration.php not found — skipping.\n");
        $failed++;

        continue;
    }

    [$host, $user, $pass, $name, $prefix] = $readConfig($configFile);

    $port = 3306;

    if (str_contains($host, ':')) {
        [$host, $portStr] = explode(':', $host, 2);
        $port             = (int) $portStr;
    }

    $db = @new mysqli($host, $user, $pass, $name, $port);

    if ($db->connect_errno !== 0) {
        fwrite(STDERR, "  DB connection failed: {$db->connect_error}\n");
        $failed++;

        continue;
    }

    $ext = $prefix . 'extensions';

    // 1. Collect the extension ids first — #__schemas and #__update_sites are
    //    keyed by them, so they have to go before the rows themselves.
    $familyWhere = "(element = 'com_livingword' AND type = 'component')
        OR (element = 'pkg_livingword' AND type = 'package')
        OR (element = 'mod_livingword' AND type = 'module')
        OR (element = 'livingword' AND type = 'plugin' AND folder = 'task')";

    if ($withScripture) {
        // element is the <libraryname>, not the folder name — see the ARS
        // element rules; lib_cwmscripture registers as 'cwmscripture'.
        $familyWhere .= "
        OR (element = 'cwmscripture' AND type = 'library')
        OR (element = 'cwmscripture' AND type = 'plugin' AND folder = 'task')
        OR (element = 'scripturelinks' AND type = 'plugin' AND folder = 'content')
        OR (element = 'pkg_cwmscripture' AND type = 'package')";
    }

    $ids = [];
    $res = $db->query("SELECT extension_id FROM `{$ext}` WHERE {$familyWhere}");

    if ($res instanceof mysqli_result) {
        while ($row = $res->fetch_row()) {
            $ids[] = (int) $row[0];
        }
    }

    if ($ids !== []) {
        $idList = implode(',', array_map('intval', $ids));

        $db->query("DELETE FROM `{$prefix}schemas` WHERE extension_id IN ({$idList})");
        $db->query("DELETE FROM `{$prefix}update_sites_extensions` WHERE extension_id IN ({$idList})");
        $db->query("DELETE FROM `{$ext}` WHERE extension_id IN ({$idList})");
    }

    echo '  removed ' . \count($ids) . " extension row(s)\n";

    // 2. Update sites orphaned by the deletions above.
    $db->query(
        "DELETE us FROM `{$prefix}update_sites` us
         LEFT JOIN `{$prefix}update_sites_extensions` use_ ON use_.update_site_id = us.update_site_id
         WHERE use_.update_site_id IS NULL"
    );

    // 3. Rows that reference the component by name rather than by id.
    $db->query("DELETE FROM `{$prefix}assets` WHERE name = 'com_livingword' OR name LIKE 'com\\_livingword.%'");
    $db->query("DELETE FROM `{$prefix}categories` WHERE extension LIKE 'com\\_livingword%'");
    $db->query("DELETE FROM `{$prefix}content_types` WHERE type_alias LIKE 'com\\_livingword.%'");
    $db->query("DELETE FROM `{$prefix}contentitem_tag_map` WHERE type_alias LIKE 'com\\_livingword.%'");

    // 4. Drop the component's own tables. Matched by prefix rather than a
    //    hardcoded list so a table added in a later migration is still caught.
    $like    = $db->real_escape_string($prefix) . 'livingword\_%';
    $res     = $db->query("SHOW TABLES LIKE '{$like}'");
    $dropped = 0;

    if ($res instanceof mysqli_result) {
        while ($row = $res->fetch_row()) {
            $db->query('DROP TABLE IF EXISTS `' . str_replace('`', '', $row[0]) . '`');
            $dropped++;
        }
    }

    echo "  dropped {$dropped} livingword_ table(s)\n";

    if ($withScripture) {
        $likeBsms = $db->real_escape_string($prefix) . 'bsms\_bible%';
        $res      = $db->query("SHOW TABLES LIKE '{$likeBsms}'");
        $n        = 0;

        if ($res instanceof mysqli_result) {
            while ($row = $res->fetch_row()) {
                $db->query('DROP TABLE IF EXISTS `' . str_replace('`', '', $row[0]) . '`');
                $n++;
            }
        }

        echo "  dropped {$n} shared scripture table(s)\n";
    }

    $db->close();

    echo "  clean.\n";
}

if ($failed > 0) {
    fwrite(STDERR, "\n{$failed} install(s) could not be reset.\n");

    exit(1);
}

echo "\nAll role=test installs reset.\n";
