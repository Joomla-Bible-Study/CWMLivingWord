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

use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/**
 * Group form controller
 *
 * @since  5.7.0
 */
class CwmgroupController extends FormController
{
    /**
     * Update a group member's role (leader/member).
     *
     * @return  void
     *
     * @since   5.7.0
     */
    public function updateMemberRole(): void
    {
        Session::checkToken('get') or die;

        $input    = $this->input;
        $memberId = $input->getInt('member_id', 0);
        $role     = $input->getCmd('role', 'member');
        $groupId  = $input->getInt('id', 0);

        /** @var \CWM\Component\Livingword\Administrator\Model\CwmgroupModel $model */
        $model = $this->getModel();
        $model->updateMemberRole($memberId, $role);

        $this->setRedirect(
            Route::_('index.php?option=com_livingword&view=cwmgroup&layout=edit&id=' . $groupId, false)
        );
    }
}
