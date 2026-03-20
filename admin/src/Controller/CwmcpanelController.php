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

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Dashboard controller
 *
 * @since  5.0.0
 */
class CwmcpanelController extends BaseController
{
    /**
     * @param   bool   $cachable   If true, the view output will be cached.
     * @param   array  $urlparams  Safe URL parameters.
     *
     * @return  static
     *
     * @throws \Exception
     * @since   5.0.0
     */
    public function display($cachable = false, $urlparams = []): static
    {
        return parent::display($cachable, $urlparams);
    }
}
