<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Administrator\Dispatcher;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Dispatcher\ComponentDispatcher;

/**
 * Component dispatcher for com_livingword admin.
 *
 * @since  5.0.0
 */
class Dispatcher extends ComponentDispatcher
{
    /**
     * @var string
     * @since 5.0.0
     */
    protected string $defaultController = 'cwmcpanel';
}
