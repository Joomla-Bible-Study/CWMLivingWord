<?php

/**
 * @package    Livingword
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\Database\DatabaseInterface;

/**
 * Install script for plg_task_livingword.
 *
 * Exists to enable the plugin on a fresh install. This has to live here rather
 * than in the component's script: pkg_livingword installs its children in
 * manifest order, and the component comes before the plugin — so at the point
 * the component's postflight runs, the plugin has no #__extensions row to
 * update yet.
 *
 * @since  5.6.0
 */
return new class () implements \Joomla\CMS\Installer\InstallerScriptInterface {
    /**
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  bool
     *
     * @since   5.6.0
     */
    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  bool
     *
     * @since   5.6.0
     */
    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  bool
     *
     * @since   5.6.0
     */
    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * @param   string            $route    install | update | uninstall
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  bool
     *
     * @since   5.6.0
     */
    public function preflight(string $route, InstallerAdapter $adapter): bool
    {
        // Capture what is installed now, before the update overwrites it —
        // postflight needs it to tell "upgrading from before auto-enable
        // existed" apart from "the admin turned this off".
        if ($route === 'update') {
            $this->fromVersion = $this->installedVersion();
        }

        return true;
    }

    /**
     * Version being upgraded from, captured in preflight. Empty when unknown.
     *
     * @var    string
     * @since  5.6.0
     */
    private string $fromVersion = '';

    /**
     * Read the plugin's currently-recorded version from #__extensions.
     *
     * @return  string  Empty when the row or its manifest cache cannot be read.
     *
     * @since   5.6.0
     */
    private function installedVersion(): string
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $query = $db->getQuery(true)
                ->select($db->quoteName('manifest_cache'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('task'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('livingword'));

            $cache = (string) $db->setQuery($query)->loadResult();
            $data  = json_decode($cache, true);

            return \is_array($data) ? (string) ($data['version'] ?? '') : '';
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Enable the plugin on a fresh install.
     *
     * Joomla registers new plugins disabled. This one carries all three
     * scheduled routines — daily reading email, weekly progress digest and
     * accountability-partner digest — so left disabled none of them run, and
     * nothing says why: an admin can configure the email settings, create a
     * scheduled task, and still get silence.
     *
     * Enabling is not itself a side effect. The plugin does nothing until a
     * scheduled task is also created, so this only removes a manual step that
     * has no reason to be manual.
     *
     * Install route only — re-enabling on every update would override an admin
     * who deliberately turned it off.
     *
     * @param   string            $route    install | update | uninstall
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  bool
     *
     * @since   5.6.0
     */
    public function postflight(string $route, InstallerAdapter $adapter): bool
    {
        // A fresh install always enables. An update only does so when coming
        // from a version that predates this script — those sites never had the
        // chance to be enabled, so leaving them off is not a choice they made.
        // From 5.6.0 onward an update leaves the setting alone.
        $shouldEnable = $route === 'install'
            || ($route === 'update' && $this->fromVersion !== '' && version_compare($this->fromVersion, '5.6.0', '<'));

        if (!$shouldEnable) {
            return true;
        }

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('task'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('livingword'))
                ->where($db->quoteName('enabled') . ' = 0');

            $db->setQuery($query)->execute();

            if ($db->getAffectedRows() > 0) {
                Factory::getApplication()->enqueueMessage(
                    'The LivingWord task plugin has been enabled. Its routines still need a task'
                    . ' created under System → Scheduled Tasks before any email is sent.',
                    'info'
                );
            }
        } catch (\Throwable $e) {
            Factory::getApplication()->enqueueMessage(
                'CWM LivingWord: could not enable the task plugin — ' . $e->getMessage()
                . ' Enable it manually under System → Plugins.',
                'warning'
            );
        }

        return true;
    }
};
