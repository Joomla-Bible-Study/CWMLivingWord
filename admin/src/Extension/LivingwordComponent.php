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
    }
}
