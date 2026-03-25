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

use CWM\Component\Livingword\Site\Helper\CwmgroupHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

/**
 * Controller for group membership actions (join, leave, remove).
 *
 * @since  5.7.0
 */
class CwmgroupController extends BaseController
{
    /**
     * Join a group by ID or invite token.
     *
     * @return  void
     *
     * @throws \Exception
     * @since   5.7.0
     */
    public function join(): void
    {
        Session::checkToken() || Session::checkToken('get') || die(Text::_('JINVALID_TOKEN'));

        $userId = (int) $this->app->getIdentity()->id;

        if ($userId === 0) {
            $this->app->enqueueMessage(Text::_('JGLOBAL_YOU_MUST_LOGIN_FIRST'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_users&view=login', false));

            return;
        }

        $db      = Factory::getContainer()->get(DatabaseInterface::class);
        $groupId = $this->input->getInt('group_id', 0);
        $token   = $this->input->getString('token', '');

        // Resolve group from invite token if no group_id provided
        if ($groupId === 0 && !empty($token)) {
            $group = CwmgroupHelper::getGroupByToken($db, $token);

            if ($group) {
                $groupId = (int) $group->id;
            }
        }

        if ($groupId === 0) {
            $this->app->enqueueMessage(Text::_('COM_LIVINGWORD_GROUP_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_livingword&view=cwmgroups', false));

            return;
        }

        $result = CwmgroupHelper::joinGroup($db, $groupId, $userId);

        if ($result) {
            $this->app->enqueueMessage(Text::_('COM_LIVINGWORD_GROUP_JOINED'), 'success');
        } else {
            $this->app->enqueueMessage(Text::_('COM_LIVINGWORD_GROUP_ALREADY_MEMBER'), 'notice');
        }

        $this->setRedirect(Route::_('index.php?option=com_livingword&view=cwmgroupdetail&group_id=' . $groupId, false));
    }

    /**
     * Leave a group.
     *
     * @return  void
     *
     * @throws \Exception
     * @since   5.7.0
     */
    public function leave(): void
    {
        Session::checkToken() || die(Text::_('JINVALID_TOKEN'));

        $userId  = (int) $this->app->getIdentity()->id;
        $groupId = $this->input->getInt('group_id', 0);

        if ($userId === 0 || $groupId === 0) {
            $this->app->enqueueMessage(Text::_('COM_LIVINGWORD_GROUP_INVALID_REQUEST'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_livingword&view=cwmgroups', false));

            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        CwmgroupHelper::leaveGroup($db, $groupId, $userId);

        $this->app->enqueueMessage(Text::_('COM_LIVINGWORD_GROUP_LEFT'), 'success');
        $this->setRedirect(Route::_('index.php?option=com_livingword&view=cwmgroups', false));
    }

    /**
     * Remove a member from a group (admin action).
     *
     * @return  void
     *
     * @throws \Exception
     * @since   5.7.0
     */
    public function removemember(): void
    {
        Session::checkToken() || die(Text::_('JINVALID_TOKEN'));

        $currentUserId = (int) $this->app->getIdentity()->id;
        $groupId       = $this->input->getInt('group_id', 0);
        $targetUserId  = $this->input->getInt('user_id', 0);

        if ($groupId === 0 || $targetUserId === 0) {
            $this->app->enqueueMessage(Text::_('COM_LIVINGWORD_GROUP_INVALID_REQUEST'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_livingword&view=cwmgroups', false));

            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        if (!CwmgroupHelper::canManageGroup($db, $groupId, $currentUserId)) {
            $this->app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_livingword&view=cwmgroupdetail&group_id=' . $groupId, false));

            return;
        }

        CwmgroupHelper::leaveGroup($db, $groupId, $targetUserId);

        $this->app->enqueueMessage(Text::_('COM_LIVINGWORD_GROUP_MEMBER_REMOVED'), 'success');
        $this->setRedirect(Route::_('index.php?option=com_livingword&view=cwmgroupdetail&group_id=' . $groupId, false));
    }
}
