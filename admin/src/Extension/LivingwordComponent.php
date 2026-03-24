<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Administrator\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Psr\Container\ContainerInterface;

/**
 * Component class for com_livingword
 *
 * @since  5.0.0
 */
class LivingwordComponent extends MVCComponent implements
    BootableExtensionInterface,
    RouterServiceInterface
{
    use RouterServiceTrait;

    /**
     * Minimum PHP version ID required (8.3.0 = 80300).
     *
     * @since 5.0.0
     */
    public const int MIN_PHP_VERSION_ID = 80300;

    /**
     * Minimum PHP version as a display string for error messages.
     *
     * @since 5.0.0
     */
    public const string MIN_PHP_VERSION = '8.3.0';

    /**
     * Minimum Joomla version required.
     *
     * @since 5.0.0
     */
    public const string MIN_JOOMLA_VERSION = '5.0.0';

    /**
     * Booting the extension.
     *
     * @param   ContainerInterface  $container  The container
     *
     * @return  void
     *
     * @since   5.0.0
     */
    public function boot(ContainerInterface $container): void
    {
        if (PHP_VERSION_ID < self::MIN_PHP_VERSION_ID) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf(
                    'COM_LIVINGWORD_ERROR_PHP_VERSION',
                    self::MIN_PHP_VERSION,
                    PHP_VERSION
                ),
                'error'
            );
        }

        if (version_compare(JVERSION, self::MIN_JOOMLA_VERSION, '<')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf(
                    'COM_LIVINGWORD_ERROR_JOOMLA_VERSION',
                    self::MIN_JOOMLA_VERSION,
                    JVERSION
                ),
                'error'
            );
        }

        if (
            class_exists('CWM\\Library\\Scripture\\LibraryVersion')
            && !\CWM\Library\Scripture\LibraryVersion::isInstalled()
        ) {
            Factory::getApplication()->enqueueMessage(
                Text::_('COM_LIVINGWORD_ERROR_SCRIPTURE_LIBRARY'),
                'warning'
            );
        }
    }
}
