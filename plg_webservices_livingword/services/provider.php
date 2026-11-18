<?php

/**
 * @package    Livingword.Plugin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Plugin\WebServices\Livingword\Extension\Livingword;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   5.7.0
     */
    public function register(Container $container): void
    {
        // CWM LivingWord needs PHP 8.4. Below it, register nothing: Joomla then boots a do-nothing plugin in this one's place,
        // so none of CWM LivingWord's code loads and the site keeps working. Keep 8.4-only
        // syntax out of this file, or the guard itself cannot run.
        if (PHP_VERSION_ID < 80400) {
            return;
        }

        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $plugin = new Livingword(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('webservices', 'livingword')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};
