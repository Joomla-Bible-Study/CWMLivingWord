<?php

/**
 * Package installer script for pkg_livingword.
 *
 * Exists for one job: install the bundled scripture stack without declaring it
 * as a package child. See installScriptureStack() for why that distinction
 * matters.
 *
 * @package    LivingWord
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @since      5.7.1
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerHelper;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Filesystem\Folder;

/**
 * Returns an anonymous class implementing InstallerScriptInterface.
 *
 * Joomla 5+ expects the script file to return an InstallerScriptInterface
 * instance directly (not define a named class).
 *
 * @since  5.7.1
 */
return new class () implements InstallerScriptInterface {
    /**
     * The scripture extensions, as #__extensions identifies them.
     *
     * @var    array<int, array{type: string, element: string, folder: string}>
     * @since  5.7.1
     */
    private const SCRIPTURE_EXTENSIONS = [
        ['type' => 'library', 'element' => 'cwmscripture',   'folder' => ''],
        ['type' => 'plugin',  'element' => 'scripturelinks', 'folder' => 'content'],
        ['type' => 'plugin',  'element' => 'cwmscripture',   'folder' => 'task'],
    ];

    /**
     * Install the bundled scripture stack, which is not a package child.
     *
     * pkg_cwmscripture ships inside this archive but pkg_livingword.xml does not
     * declare it. PackageAdapter::removeExtensionFiles() uninstalls every entry
     * in the installed manifest's <files> by element — package_id has no say —
     * so declaring the scripture extensions, as 5.7.0 and earlier did, meant
     * removing LivingWord took a stack Proclaim was still using.
     *
     * Runs in preflight because com_livingword's own postflight registers itself
     * in the library's consumer registry, and registerScriptureConsumer() returns
     * silently when ConsumerRegistry cannot be autoloaded. Installing later would
     * leave LivingWord unregistered.
     *
     * Skips when an equal or newer pkg_cwmscripture is already installed, so a
     * site carrying a current one — from Proclaim or standalone — is never
     * downgraded.
     *
     * @param   InstallerAdapter  $adapter  The installer adapter
     *
     * @return  bool  False only when the stack is required and could not be installed
     *
     * @since   5.7.1
     */
    private function installScriptureStack(InstallerAdapter $adapter): bool
    {
        $zip = $adapter->getParent()->getPath('source') . '/packages/pkg_cwmscripture.zip';

        if (!is_file($zip)) {
            // A site can legitimately carry the stack from Proclaim or standalone.
            if ($this->scriptureLibraryPresent()) {
                return true;
            }

            Factory::getApplication()->enqueueMessage(
                'The scripture package is missing from this LivingWord archive and no scripture '
                . 'library is installed. Install pkg_cwmscripture first, then retry.',
                'error'
            );

            return false;
        }

        try {
            $bundled   = $this->manifestVersionInZip($zip);
            $installed = $this->installedVersion('pkg_cwmscripture', 'package');

            if ($installed !== null && $bundled !== null && version_compare($installed, $bundled, '>=')) {
                Log::add(
                    "pkg_livingword: pkg_cwmscripture {$installed} already installed (bundled {$bundled}).",
                    Log::INFO,
                    'com_livingword'
                );

                return true;
            }

            $package = InstallerHelper::unpack($zip, true);

            if ($package === false) {
                throw new \RuntimeException('could not unpack pkg_cwmscripture.zip');
            }

            $installer = new Installer();
            $installer->setDatabase(Factory::getContainer()->get(DatabaseInterface::class));
            $result = $installer->install($package['dir']);

            // Only the unpacked copy. $zip lives in our own extracted source tree,
            // which the parent installer cleans up.
            if (is_dir($package['dir'])) {
                Folder::delete($package['dir']);
            }

            if (!$result) {
                throw new \RuntimeException('the installer reported a failure');
            }

            Log::add('pkg_livingword: installed the bundled scripture stack.', Log::INFO, 'com_livingword');

            return true;
        } catch (\Throwable $e) {
            // A failed refresh is not worth blocking when a usable library is there.
            if ($this->scriptureLibraryPresent()) {
                Log::add(
                    'pkg_livingword: could not refresh the scripture stack (' . $e->getMessage()
                    . ') — continuing with the installed one.',
                    Log::WARNING,
                    'com_livingword'
                );

                return true;
            }

            Factory::getApplication()->enqueueMessage(
                'LivingWord could not install the scripture library it depends on: ' . $e->getMessage(),
                'error'
            );

            return false;
        }
    }

    /**
     * Repoint scripture extensions still recorded as children of this package.
     *
     * 5.7.0 and earlier installed them directly, stamping package_id with
     * pkg_livingword's id. Dropping them from <files> stops the uninstall
     * cascade, but the stale row still shows them as ours in the Extensions
     * manager — so hand them to pkg_cwmscripture, or orphan them if it is
     * somehow absent.
     *
     * @return  void
     *
     * @since   5.7.1
     */
    private function releaseScriptureExtensions(): void
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $ours = $this->extensionId('pkg_livingword', 'package');

            if ($ours === null) {
                return;
            }

            $owner = $this->extensionId('pkg_cwmscripture', 'package') ?? 0;

            foreach (self::SCRIPTURE_EXTENSIONS as $extension) {
                $query = $db->createQuery()
                    ->update($db->quoteName('#__extensions'))
                    ->set($db->quoteName('package_id') . ' = :owner')
                    ->where($db->quoteName('type') . ' = :type')
                    ->where($db->quoteName('element') . ' = :element')
                    ->where($db->quoteName('folder') . ' = :folder')
                    ->where($db->quoteName('package_id') . ' = :ours')
                    ->bind(':owner', $owner, ParameterType::INTEGER)
                    ->bind(':type', $extension['type'])
                    ->bind(':element', $extension['element'])
                    ->bind(':folder', $extension['folder'])
                    ->bind(':ours', $ours, ParameterType::INTEGER);

                $db->setQuery($query)->execute();
            }
        } catch (\Throwable $e) {
            Log::add(
                'pkg_livingword: could not release the scripture extensions (' . $e->getMessage() . ').',
                Log::WARNING,
                'com_livingword'
            );
        }
    }

    /**
     * Whether a usable scripture library is present on disk.
     *
     * On disk rather than in #__extensions: preflight can run before the row
     * exists, and the row can outlive the files.
     *
     * @return  bool
     *
     * @since   5.7.1
     */
    private function scriptureLibraryPresent(): bool
    {
        return is_file(JPATH_LIBRARIES . '/cwmscripture/src/Helper/ScriptureHelper.php');
    }

    /**
     * Extension id of an installed extension, or null when it is not installed.
     *
     * @param   string  $element  Extension element
     * @param   string  $type     Extension type
     *
     * @return  int|null
     *
     * @since   5.7.1
     */
    private function extensionId(string $element, string $type): ?int
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->createQuery()
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('element') . ' = :element')
            ->where($db->quoteName('type') . ' = :type')
            ->bind(':element', $element)
            ->bind(':type', $type);

        $id = $db->setQuery($query)->loadResult();

        return $id === null ? null : (int) $id;
    }

    /**
     * Version of an installed extension, or null when it is not installed.
     *
     * @param   string  $element  Extension element
     * @param   string  $type     Extension type
     *
     * @return  string|null
     *
     * @since   5.7.1
     */
    private function installedVersion(string $element, string $type): ?string
    {
        try {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->createQuery()
                ->select($db->quoteName('manifest_cache'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = :element')
                ->where($db->quoteName('type') . ' = :type')
                ->bind(':element', $element)
                ->bind(':type', $type);

            $cache = $db->setQuery($query)->loadResult();

            if (!$cache) {
                return null;
            }

            $decoded = json_decode((string) $cache, true, 512, JSON_THROW_ON_ERROR);

            return $decoded['version'] ?? null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Version declared by the manifest inside a package zip.
     *
     * @param   string  $zip  Absolute path to the zip
     *
     * @return  string|null
     *
     * @since   5.7.1
     */
    private function manifestVersionInZip(string $zip): ?string
    {
        $archive = new \ZipArchive();

        if ($archive->open($zip) !== true) {
            return null;
        }

        $version = null;

        for ($i = 0; $i < $archive->numFiles; $i++) {
            $name = $archive->getNameIndex($i);

            // The package manifest sits at the archive root.
            if ($name === false || substr_count($name, '/') > 0 || !str_ends_with($name, '.xml')) {
                continue;
            }

            $xml = simplexml_load_string((string) $archive->getFromIndex($i));

            if ($xml !== false && isset($xml->version)) {
                $version = (string) $xml->version;

                break;
            }
        }

        $archive->close();

        return $version;
    }

    /**
     * Runs before install/update/uninstall.
     *
     * @param   string            $type     Install type
     * @param   InstallerAdapter  $adapter  The installer adapter
     *
     * @return  bool  False aborts the install
     *
     * @since   5.7.1
     */
    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type === 'uninstall') {
            return true;
        }

        return $this->installScriptureStack($adapter);
    }

    /**
     * Runs on install.
     *
     * @param   InstallerAdapter  $adapter  The installer adapter
     *
     * @return  bool
     *
     * @since   5.7.1
     */
    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Runs on update.
     *
     * @param   InstallerAdapter  $adapter  The installer adapter
     *
     * @return  bool
     *
     * @since   5.7.1
     */
    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Runs on uninstall.
     *
     * Deliberately does not touch the scripture stack: it is shared with Proclaim
     * and ScriptureLinks, and must survive — along with every downloaded
     * translation — when LivingWord goes. That is the whole point of this file.
     *
     * @param   InstallerAdapter  $adapter  The installer adapter
     *
     * @return  bool
     *
     * @since   5.7.1
     */
    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Runs after install/update/uninstall.
     *
     * @param   string            $type     Install type
     * @param   InstallerAdapter  $adapter  The installer adapter
     *
     * @return  bool
     *
     * @since   5.7.1
     */
    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        if ($type !== 'uninstall') {
            $this->releaseScriptureExtensions();
        }

        return true;
    }
};
