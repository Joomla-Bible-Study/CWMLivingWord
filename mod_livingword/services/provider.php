<?php

/**
 * @package    Livingword.Module
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        // CWM LivingWord needs PHP 8.4. Below it, register nothing: Joomla then renders nothing for this module,
        // so none of CWM LivingWord's code loads and the site keeps working. Keep 8.4-only
        // syntax out of this file, or the guard itself cannot run.
        if (PHP_VERSION_ID < 80400) {
            return;
        }

        // The two namespaces differ on purpose. ModuleDispatcherFactory appends the
        // client segment itself, so it takes the base namespace; HelperFactory appends
        // nothing, so it takes the full one. Making them match breaks one or the other.
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\CWM\\Module\\Livingword'));
        $container->registerServiceProvider(new HelperFactory('\\CWM\\Module\\Livingword\\Site\\Helper'));
        $container->registerServiceProvider(new Module());
    }
};
