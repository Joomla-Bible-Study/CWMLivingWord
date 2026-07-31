<?php

/**
 * @package    Livingword.Plugin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\Database\DatabaseInterface;

/**
 * Install script for plg_webservices_livingword.
 *
 * Enables the plugin on install, matching how the rest of the CWM family
 * treats the plugins its packages ship — Proclaim's package script does the
 * same through its own enablePlugin() helper, and plg_task_livingword enables
 * itself here.
 *
 * What enabling does and does not open, since this one registers HTTP routes:
 *
 *   - Joomla's API application has to be reachable at all, which is a site
 *     decision made before this plugin exists.
 *   - Every route is registered with `public => false` except catalog reads,
 *     and those only become public if an administrator switches on
 *     `public_reads`, which defaults to off.
 *   - So an enabled plugin on a default site serves nothing to anyone without
 *     a Joomla API token, and tokens are created per user, deliberately.
 *
 * The opt-in therefore sits at the token, not at the plugin. Leaving the
 * plugin disabled instead would mean the API silently 404s for a site that
 * created a token and expected it to work — a worse failure, because nothing
 * says why.
 *
 * @since  5.7.0
 */
return new class () implements \Joomla\CMS\Installer\InstallerScriptInterface {
    /**
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  bool
     *
     * @since   5.7.0
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
     * @since   5.7.0
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
     * @since   5.7.0
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
     * @since   5.7.0
     */
    public function preflight(string $route, InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Enable the plugin on a fresh install.
     *
     * Install route only. An administrator who turns the API off afterwards
     * means it, and an update must not quietly switch it back on.
     *
     * @param   string            $route    install | update | uninstall
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  bool
     *
     * @since   5.7.0
     */
    public function postflight(string $route, InstallerAdapter $adapter): bool
    {
        if ($route !== 'install') {
            return true;
        }

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $query = $db->getQuery(true)
                ->update($db->quoteName('#__extensions'))
                ->set($db->quoteName('enabled') . ' = 1')
                ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                ->where($db->quoteName('folder') . ' = ' . $db->quote('webservices'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('livingword'))
                ->where($db->quoteName('enabled') . ' = 0');

            $db->setQuery($query)->execute();

            if ($db->getAffectedRows() > 0) {
                Factory::getApplication()->enqueueMessage(
                    'The LivingWord Web Services plugin has been enabled. The API still requires a'
                    . ' Joomla API token; catalog reads stay private unless you switch on'
                    . ' "Public catalog reads" in the plugin options.',
                    'info'
                );
            }
        } catch (\Throwable $e) {
            Factory::getApplication()->enqueueMessage(
                'CWM LivingWord: could not enable the Web Services plugin — ' . $e->getMessage()
                . ' Enable it manually under System → Plugins.',
                'warning'
            );
        }

        return true;
    }
};
