<?php

/**
 * @package    Livingword.Tests
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Tests;

use PHPUnit\Framework\TestCase;

/**
 * What pkg_livingword.xml may and may not claim ownership of.
 *
 * PackageAdapter::removeExtensionFiles() reads the installed manifest and
 * uninstalls every <file> in it by element — package_id has no say. Up to 5.7.0
 * that list named lib_cwmscripture, plg_content_scripturelinks and
 * plg_task_cwmscripture, so removing LivingWord took a scripture stack Proclaim
 * was still using, and every downloaded translation with it.
 *
 * The fix is a package that ships pkg_cwmscripture.zip as an undeclared extra
 * and installs it from build/script.install.php. Three things have to stay true
 * for that to keep working, and each is one edit away from silently reverting.
 *
 * @since  __DEPLOY_VERSION__
 */
class PackageManifestTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';

    private const MANIFEST = 'build/pkg_livingword.xml';

    /**
     * Elements that belong to the shared scripture stack, as <file id> spells them.
     */
    private const SCRIPTURE_IDS = ['cwmscripture', 'scripturelinks'];

    /**
     * The manifest declares nothing from the scripture stack.
     *
     * The regression itself. Re-adding a <file> for any of them restores the
     * uninstall cascade — nothing else in the build or the test suite notices.
     */
    public function testTheManifestClaimsNoScriptureExtension(): void
    {
        foreach ($this->declaredFiles() as $file => $id) {
            $this->assertNotContains(
                $id,
                self::SCRIPTURE_IDS,
                self::MANIFEST . ' declares ' . $file . '. PackageAdapter uninstalls it by element when'
                . ' LivingWord is removed, taking a stack shared with Proclaim.'
            );
        }
    }

    /**
     * The scriptfile is declared, and it is a file that exists.
     *
     * Without it the bundled pkg_cwmscripture.zip is inert: it rides in the
     * archive that nothing reads, and a clean install comes up with no scripture
     * library at all.
     */
    public function testTheManifestRunsTheInstallerThatMakesUpForThat(): void
    {
        $xml = simplexml_load_file(self::ROOT . '/' . self::MANIFEST);

        $this->assertNotFalse($xml, self::MANIFEST . ' is not readable XML.');
        $this->assertTrue(
            isset($xml->scriptfile),
            self::MANIFEST . ' declares no <scriptfile>, so nothing installs the bundled scripture package.'
        );

        $scriptfile = (string) $xml->scriptfile;
        $configured = $this->buildConfig()['package']['installer'] ?? null;

        $this->assertSame(
            'build/' . $scriptfile,
            $configured,
            'cwm-build.config.json package.installer must point at the manifest\'s <scriptfile>,'
            . ' or the build ships a manifest naming a script that is not in the archive.'
        );

        $this->assertFileExists(self::ROOT . '/' . $configured);
    }

    /**
     * No <blockChildUninstall>.
     *
     * It makes Joomla refuse to uninstall any extension whose package_id points
     * at us — and on a site upgraded from 5.7.0 the scripture extensions still
     * carry that stale package_id until postflight clears it. Setting it would
     * re-assert exactly the ownership this package gave up.
     */
    public function testTheManifestDoesNotBlockChildUninstalls(): void
    {
        $xml = simplexml_load_file(self::ROOT . '/' . self::MANIFEST);

        $this->assertFalse(
            isset($xml->blockChildUninstall),
            self::MANIFEST . ' sets <blockChildUninstall>, which re-claims extensions this package'
            . ' deliberately no longer owns.'
        );
    }

    /**
     * The build's expected entries are the manifest's children plus the extra.
     *
     * The one that earns its keep: manifest and build config drift apart
     * silently, and the failure mode is a package whose scripture zip is simply
     * missing — which the installer then reports as an error on a clean site,
     * long after the build passed.
     */
    public function testTheBuildShipsExactlyTheDeclaredChildrenPlusTheScripturePackage(): void
    {
        $config   = $this->buildConfig();
        $expected = $config['package']['verify']['expectedEntries'] ?? [];

        $this->assertNotEmpty($expected, 'cwm-build.config.json declares no expectedEntries.');

        $wanted = [
            basename(self::MANIFEST),
            basename((string) $config['package']['installer']),
            'packages/pkg_cwmscripture.zip',
        ];

        foreach (array_keys($this->declaredFiles()) as $file) {
            $wanted[] = 'packages/' . $file;
        }

        sort($wanted);
        sort($expected);

        $this->assertSame(
            $wanted,
            $expected,
            'The package manifest and cwm-build.config.json disagree about what the archive holds.'
        );
    }

    /**
     * Every include the build assembles is one the archive is expected to hold.
     *
     * Guards the assertion above from the other side: an include added to the
     * config but left out of expectedEntries would ship unverified.
     */
    public function testEveryBuildIncludeIsAnExpectedEntry(): void
    {
        $config   = $this->buildConfig();
        $expected = $config['package']['verify']['expectedEntries'] ?? [];

        foreach ($config['package']['includes'] ?? [] as $include) {
            $this->assertContains(
                'packages/' . $include['outputName'],
                $expected,
                $include['outputName'] . ' is built but not listed in expectedEntries.'
            );
        }
    }

    /**
     * Inner zip filename => the extension element the <file> claims.
     *
     * @return  array<string, string>
     */
    private function declaredFiles(): array
    {
        $xml = simplexml_load_file(self::ROOT . '/' . self::MANIFEST);

        $this->assertNotFalse($xml, self::MANIFEST . ' is not readable XML.');

        $files = [];

        foreach ($xml->files->children() as $child) {
            $files[(string) $child] = (string) $child->attributes()->id;
        }

        $this->assertNotEmpty($files, self::MANIFEST . ' declares no <file> entries.');

        return $files;
    }

    /**
     * @return  array<string, mixed>
     */
    private function buildConfig(): array
    {
        return json_decode((string) file_get_contents(self::ROOT . '/cwm-build.config.json'), true);
    }
}
