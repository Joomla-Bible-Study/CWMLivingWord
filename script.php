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
use Joomla\CMS\Language\Text;

/**
 * Script file of com_livingword component
 *
 * @since  5.0.0
 */
class Com_livingwordInstallerScript
{
    /**
     * Minimum PHP version required.
     *
     * @var string
     * @since 5.0.0
     */
    protected string $minimumPhp = '8.3.0';

    /**
     * Minimum Joomla version required.
     *
     * @var string
     * @since 5.0.0
     */
    protected string $minimumJoomla = '5.0.0';

    /**
     * Method to install the extension.
     *
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  bool
     *
     * @since   5.0.0
     */
    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Method to uninstall the extension.
     *
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  bool
     *
     * @since   5.0.0
     */
    public function uninstall(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Method to update the extension.
     *
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  bool
     *
     * @since   5.0.0
     */
    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Function called before extension installation/update/removal.
     *
     * @param   string            $route    Which action is happening (install|uninstall|update)
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  bool  True on success
     *
     * @since   5.0.0
     */
    public function preflight(string $route, InstallerAdapter $adapter): bool
    {
        if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_LIVINGWORD requires PHP %s or later. You are running PHP %s.', $this->minimumPhp, PHP_VERSION),
                'error'
            );

            return false;
        }

        if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_LIVINGWORD requires Joomla %s or later.', $this->minimumJoomla),
                'error'
            );

            return false;
        }

        return true;
    }

    /**
     * Function called after extension installation/update/removal.
     *
     * @param   string            $route    Which action is happening (install|uninstall|update)
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  bool
     *
     * @since   5.0.0
     */
    public function postflight(string $route, InstallerAdapter $adapter): bool
    {
        if ($route === 'install') {
            Factory::getApplication()->enqueueMessage(
                'CWM LivingWord 5.0.0 has been installed successfully.',
                'message'
            );
        }

        if ($route === 'update') {
            Factory::getApplication()->enqueueMessage(
                'CWM LivingWord has been updated to version 5.0.0.',
                'message'
            );
        }

        return true;
    }
}
