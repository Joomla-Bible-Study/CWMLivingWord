<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmuserHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Public unsubscribe controller — no login required.
 *
 * Handles one-click email unsubscribe via token from email footer links.
 *
 * @since  5.2.0
 */
class CwmunsubscribeController extends BaseController
{
    /**
     * Unsubscribe a user from daily reading emails using their token.
     *
     * URL: index.php?option=com_livingword&task=cwmunsubscribe.unsubscribe&token=XXX
     *
     * @return  void
     *
     * @since   5.2.0
     */
    public function unsubscribe(): void
    {
        $token = $this->input->getString('token', '');
        $db    = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        $success = CwmuserHelper::unsubscribeByToken($db, $token);

        $app = $this->app;
        $app->setUserState('com_livingword.unsubscribe.success', $success);

        $this->setRedirect(
            \Joomla\CMS\Router\Route::_('index.php?option=com_livingword&view=cwmunsubscribe', false)
        );
    }
}
