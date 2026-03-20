<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Plans list controller
 *
 * @since  5.0.0
 */
class CwmplansController extends AdminController
{
    /**
     * @param   string  $name    The model name.
     * @param   string  $prefix  The class prefix.
     * @param   array   $config  Config values.
     *
     * @return  BaseDatabaseModel
     *
     * @since   5.0.0
     */
    public function getModel($name = 'Cwmplan', $prefix = 'Administrator', $config = ['ignore_request' => true]): BaseDatabaseModel
    {
        return parent::getModel($name, $prefix, $config);
    }
}
