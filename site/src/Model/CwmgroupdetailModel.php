<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmgroupHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Group detail model for site frontend.
 *
 * @since  5.7.0
 */
class CwmgroupdetailModel extends BaseDatabaseModel
{
    /**
     * Get a single group with plan info.
     *
     * @param   int  $groupId  Group ID
     *
     * @return  ?object  Group object or null
     *
     * @since   5.7.0
     */
    public function getGroup(int $groupId): ?object
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('g.id'),
                $db->quoteName('g.name'),
                $db->quoteName('g.description'),
                $db->quoteName('g.plan_id'),
                $db->quoteName('g.start_date'),
                $db->quoteName('g.invite_token'),
                $db->quoteName('g.published'),
                $db->quoteName('p.title', 'plan_title'),
                $db->quoteName('p.description', 'plan_description'),
            ])
            ->from($db->quoteName('#__livingword_groups', 'g'))
            ->join('LEFT', $db->quoteName('#__livingword_plans', 'p')
                . ' ON p.' . $db->quoteName('id') . ' = g.' . $db->quoteName('plan_id'))
            ->where($db->quoteName('g.id') . ' = ' . $groupId)
            ->where($db->quoteName('g.published') . ' = 1');

        $db->setQuery($query);

        return $db->loadObject() ?: null;
    }

    /**
     * Get member progress data for a group.
     *
     * @param   int  $groupId  Group ID
     *
     * @return  array  Array of member progress objects
     *
     * @since   5.7.0
     */
    public function getMembers(int $groupId): array
    {
        $db = $this->getDatabase();

        return CwmgroupHelper::getGroupMemberProgress($db, $groupId);
    }

    /**
     * Get the current user's role in a group.
     *
     * @param   int  $groupId  Group ID
     *
     * @return  string  'group_admin', 'member', or '' (not a member)
     *
     * @throws \Exception
     * @since   5.7.0
     */
    public function getUserRole(int $groupId): string
    {
        $db     = $this->getDatabase();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        if ($userId === 0) {
            return '';
        }

        $query = $db->getQuery(true)
            ->select($db->quoteName('role'))
            ->from($db->quoteName('#__livingword_group_members'))
            ->where($db->quoteName('group_id') . ' = ' . $groupId)
            ->where($db->quoteName('user_id') . ' = ' . $userId);

        $db->setQuery($query);
        $role = $db->loadResult();

        return $role ?: '';
    }

    /**
     * Check if the current user can view member progress for a group.
     *
     * @param   int  $groupId  Group ID
     *
     * @return  bool
     *
     * @throws \Exception
     * @since   5.7.0
     */
    public function canViewMemberProgress(int $groupId): bool
    {
        $db     = $this->getDatabase();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        if ($userId === 0) {
            return false;
        }

        return CwmgroupHelper::canManageGroup($db, $groupId, $userId);
    }
}
