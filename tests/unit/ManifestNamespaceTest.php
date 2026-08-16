<?php

/**
 * @package    Livingword.Tests
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The namespaces our manifests declare, and the source they promise to hold.
 *
 * The other half of ServiceProviderNamespaceTest. That one asserts the
 * namespace services/provider.php hands the dispatcher factory; this one
 * asserts the manifest's <namespace>, which is what libraries/namespacemap.php
 * turns into the autoload_psr4.php entry. Both halves have now been broken
 * independently, one release apart, in the same way — by including the client
 * segment in a string Joomla appends it to (#139 for the manifest, #141 for the
 * provider). Neither break was visible: the module simply rendered nothing.
 *
 * The rule comes from two places in core, reading the same base namespace:
 *
 *   libraries/namespacemap.php          appends 'Administrator\' or 'Site\'
 *                                       (and 'Api\' for a component) to the
 *                                       manifest namespace
 *   ModuleDispatcherFactory::createDispatcher()
 *                                       appends the same segment to the
 *                                       provider's namespace
 *
 * So the manifest and the provider must name the same base namespace, and
 * neither may carry the segment itself. Plugins take the earlier branch in
 * namespacemap.php, which appends nothing — hence the segment rule applies to
 * components and modules only, while the directory and class assertions apply
 * to everything.
 *
 * These read the real files rather than booting Joomla: the CMS is not on the
 * unit-test autoloader, and the bug lives in a string, not in behaviour.
 *
 * @since  __DEPLOY_VERSION__
 */
class ManifestNamespaceTest extends TestCase
{
    /**
     * Repository root, from this file rather than JPATH_BASE — data providers
     * run before a test-case constant would help, and this holds whatever the
     * working directory is.
     */
    private const ROOT = __DIR__ . '/../..';

    /**
     * The manifests the build itself knows about, from cwm-build.config.json —
     * the same list cwm-bump versions and cwm-package assembles. Deriving them
     * from there rather than from a hardcoded list means an extension added to
     * the build is covered here the same day.
     *
     * @return  array<string, array{0: string, 1: string}>
     */
    public static function manifestProvider(): array
    {
        $config = json_decode(
            (string) file_get_contents(self::ROOT . '/cwm-build.config.json'),
            true
        );

        $manifests = [];

        foreach ($config['manifests']['extensions'] ?? [] as $extension) {
            $manifests[$extension['path']] = [$extension['path'], $extension['type']];
        }

        return $manifests;
    }

    /**
     * Every manifest in the tree is one the build config declares.
     *
     * Guards the provider above from going quietly stale: a fifth extension
     * added to the repo but never registered with the build would otherwise be
     * covered by nothing here, and would ship unversioned besides.
     */
    public function testTheBuildConfigDeclaresEveryManifestInTheTree(): void
    {
        $declared = array_keys(self::manifestProvider());

        $this->assertNotEmpty($declared, 'cwm-build.config.json declares no extension manifests.');

        $found = [];

        foreach ([...glob(self::ROOT . '/*.xml'), ...glob(self::ROOT . '/*/*.xml')] as $file) {
            // A manifest that is a symlink is not a source of truth.
            // admin/livingword.xml is one, pointing at the root manifest.
            if (is_link($file) || !str_contains((string) file_get_contents($file), '<namespace')) {
                continue;
            }

            $found[] = ltrim(str_replace(realpath(self::ROOT), '', (string) realpath($file)), '/');
        }

        sort($found);
        sort($declared);

        $this->assertSame(
            $declared,
            $found,
            'A manifest declaring a namespace is missing from cwm-build.config.json (or vice versa).'
        );
    }

    /**
     * The exact mistake, twice: a namespace that already carries the segment
     * Joomla is about to append. `CWM\Module\Livingword\Site` becomes
     * `CWM\Module\Livingword\Site\Site\` in the autoload map, which matches no
     * class, and the extension stops loading with nothing said anywhere.
     */
    #[DataProvider('manifestProvider')]
    public function testAComponentOrModuleNamespaceDoesNotCarryTheClientSegment(
        string $path,
        string $type
    ): void {
        if (!\in_array($type, ['component', 'module'], true)) {
            // Plugins return early in namespacemap.php with no segment
            // appended, so the rule does not apply to them.
            $this->expectNotToPerformAssertions();

            return;
        }

        $namespace = $this->namespaceIn($path);

        foreach (['Site', 'Administrator', 'Api'] as $segment) {
            $this->assertStringEndsNotWith(
                '\\' . $segment,
                $namespace,
                $path . ' declares ' . $namespace . ', and namespacemap.php appends \\' . $segment
                . ' to it — the autoload entry would point at a namespace no class declares.'
            );
        }
    }

    /**
     * The namespace plus its path attribute has to land on real directories,
     * one per client segment. A manifest naming a directory that does not
     * exist produces no autoload entry at all — namespacemap.php skips it.
     */
    #[DataProvider('manifestProvider')]
    public function testTheNamespaceMapsToSourceDirectoriesThatExist(string $path, string $type): void
    {
        $directories = $this->sourceDirectories($path, $type);

        $this->assertNotEmpty(
            $directories,
            'No source directory could be derived from ' . $path . ' — the assertions below would be vacuous.'
        );

        foreach ($directories as $directory => $prefix) {
            $this->assertDirectoryExists(
                self::ROOT . '/' . $directory,
                $path . ' promises ' . $prefix . ' in ' . $directory . ', which does not exist.'
            );
        }
    }

    /**
     * ...and the classes in those directories have to declare the namespace
     * the manifest promised. This is the assertion that catches a rename on
     * either side — manifest edited without the source, or the reverse.
     */
    #[DataProvider('manifestProvider')]
    public function testEveryClassDeclaresTheNamespaceItsManifestPromises(string $path, string $type): void
    {
        $checked = 0;

        foreach ($this->sourceDirectories($path, $type) as $directory => $prefix) {
            foreach ($this->phpFilesIn(self::ROOT . '/' . $directory) as $file) {
                $declared = $this->namespaceDeclaredIn($file);

                $this->assertNotSame(
                    '',
                    $declared,
                    $file . ' declares no namespace, so it is not autoloadable.'
                );

                $this->assertTrue(
                    $declared === $prefix || str_starts_with($declared, $prefix . '\\'),
                    $file . ' declares ' . $declared . ', outside the ' . $prefix
                    . ' prefix ' . $path . ' registers for that directory.'
                );

                $checked++;
            }
        }

        $this->assertGreaterThan(0, $checked, 'No PHP source was found for ' . $path . '.');
    }

    /**
     * The two halves, compared directly: what the manifest registers and what
     * the provider hands the factories that append the client segment
     * themselves. HelperFactory is deliberately absent — it appends only the
     * helper name, so it takes the full namespace, and asserting it here would
     * reintroduce the bug in the other direction. ServiceProviderNamespaceTest
     * covers that one.
     *
     * @return  array<string, array{0: string, 1: string, 2: string[]}>
     */
    public static function providerAgreementProvider(): array
    {
        return [
            'module' => [
                'mod_livingword/mod_livingword.xml',
                'mod_livingword/services/provider.php',
                ['ModuleDispatcherFactory'],
            ],
            'component' => [
                'livingword.xml',
                'admin/services/provider.php',
                ['MVCFactory', 'ComponentDispatcherFactory', 'RouterFactory', 'CategoryFactory'],
            ],
        ];
    }

    #[DataProvider('providerAgreementProvider')]
    public function testTheManifestAndItsServiceProviderAgreeOnTheBaseNamespace(
        string $manifest,
        string $provider,
        array $factories
    ): void {
        $expected = $this->namespaceIn($manifest);
        $source   = (string) file_get_contents(self::ROOT . '/' . $provider);

        foreach ($factories as $factory) {
            $pattern = '/new\s+' . preg_quote($factory, '/') . "\(\s*'([^']+)'\s*\)/";

            // Without this the regex could match nothing and the loop would
            // pass having asserted nothing at all.
            $this->assertMatchesRegularExpression(
                $pattern,
                $source,
                $factory . ' is not registered with a literal namespace in ' . $provider . '.'
            );

            preg_match($pattern, $source, $matches);

            $this->assertSame(
                $expected,
                trim(stripslashes($matches[1]), '\\'),
                $provider . ' hands ' . $factory . ' a different base namespace than ' . $manifest
                . ' registers — one of the two resolves to classes that do not exist.'
            );
        }
    }

    /**
     * The namespace a manifest declares, without leading or trailing slashes.
     */
    private function namespaceIn(string $manifestPath): string
    {
        $xml = simplexml_load_file(self::ROOT . '/' . $manifestPath);

        $this->assertNotFalse($xml, $manifestPath . ' is not readable XML.');
        $this->assertTrue(isset($xml->namespace), $manifestPath . ' declares no <namespace>.');

        return trim((string) $xml->namespace, '\\');
    }

    /**
     * Source directory => namespace prefix, as namespacemap.php would build it.
     *
     * A component's clients live in three roots, which the manifest names in
     * its own <files folder>, <administration><files folder> and
     * <api><files folder> attributes; a module's single client comes from the
     * `client` attribute; a plugin has none.
     *
     * @return  array<string, string>
     */
    private function sourceDirectories(string $manifestPath, string $type): array
    {
        $xml       = simplexml_load_file(self::ROOT . '/' . $manifestPath);
        $namespace = $this->namespaceIn($manifestPath);
        $path      = trim((string) $xml->namespace->attributes()->path, '/');
        $root      = trim(\dirname($manifestPath), '.');

        if ($type === 'component') {
            $clients = [
                'Administrator' => (string) ($xml->administration->files['folder'] ?? ''),
                'Site'          => (string) ($xml->files['folder'] ?? ''),
            ];

            // Only when the manifest ships an API client: namespacemap.php adds
            // the Api\ entry only if JPATH_API holds that directory.
            if (isset($xml->api->files)) {
                $clients['Api'] = (string) $xml->api->files['folder'];
            }

            $directories = [];

            foreach ($clients as $segment => $folder) {
                if ($folder === '') {
                    continue;
                }

                $directories[trim($folder, '/') . '/' . $path] = $namespace . '\\' . $segment;
            }

            return $directories;
        }

        $directory = ($root === '' ? '' : $root . '/') . $path;

        if ($type === 'module') {
            // client="site" (the default) => Site\, client="administrator" =>
            // Administrator\, exactly as namespacemap.php derives it from where
            // the module is installed.
            $client = ucfirst((string) ($xml['client'] ?? 'site') ?: 'site');

            return [$directory => $namespace . '\\' . $client];
        }

        return [$directory => $namespace];
    }

    /**
     * Every .php file below a directory.
     *
     * @return  string[]
     */
    private function phpFilesIn(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * The namespace a PHP file declares, or an empty string when it declares none.
     */
    private function namespaceDeclaredIn(string $file): string
    {
        preg_match('/^namespace\s+([^;]+);/m', (string) file_get_contents($file), $matches);

        return trim($matches[1] ?? '');
    }
}
