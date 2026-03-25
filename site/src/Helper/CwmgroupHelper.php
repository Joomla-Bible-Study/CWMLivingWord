<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Group reading plans helper.
 *
 * Manages group membership, invite tokens, and member progress aggregation.
 *
 * @since  5.7.0
 */
class CwmgroupHelper
{
    /**
     * Generate a random invite token for a group.
     *
     * @return  string  64-character hex token
     *
     * @since   5.7.0
     */
    public static function generateInviteToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Look up a published group by its invite token.
     *
     * @param   DatabaseInterface  $db     Database instance
     * @param   string             $token  Invite token
     *
     * @return  ?object  Group row or null
     *
     * @since   5.7.0
     */
    public static function getGroupByToken(DatabaseInterface $db, string $token): ?object
    {
        if (empty($token)) {
            return null;
        }

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__livingword_groups'))
            ->where($db->quoteName('invite_token') . ' = ' . $db->quote($token))
            ->where($db->quoteName('published') . ' = 1');

        $db->setQuery($query);

        return $db->loadObject() ?: null;
    }

    /**
     * Get all groups the user is a member of.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  Joomla user ID
     *
     * @return  array  Array of group objects with id, name, plan title, role, member_count, start_date
     *
     * @since   5.7.0
     */
    public static function getUserGroups(DatabaseInterface $db, int $userId): array
    {
        if ($userId === 0) {
            return [];
        }

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('g.id'),
                $db->quoteName('g.name'),
                $db->quoteName('g.start_date'),
                $db->quoteName('p.title', 'plan_title'),
                $db->quoteName('gm.role'),
                '(SELECT COUNT(*) FROM ' . $db->quoteName('#__livingword_group_members') . ' AS mc'
                    . ' WHERE mc.' . $db->quoteName('group_id') . ' = g.' . $db->quoteName('id')
                    . ') AS ' . $db->quoteName('member_count'),
            ])
            ->from($db->quoteName('#__livingword_group_members', 'gm'))
            ->join('INNER', $db->quoteName('#__livingword_groups', 'g')
                . ' ON g.' . $db->quoteName('id') . ' = gm.' . $db->quoteName('group_id'))
            ->join('LEFT', $db->quoteName('#__livingword_plans', 'p')
                . ' ON p.' . $db->quoteName('id') . ' = g.' . $db->quoteName('plan_id'))
            ->where($db->quoteName('gm.user_id') . ' = ' . $userId)
            ->where($db->quoteName('g.published') . ' = 1')
            ->order($db->quoteName('g.name') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Get published groups the user is NOT a member of.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  Joomla user ID
     *
     * @return  array  Array of group objects
     *
     * @since   5.7.0
     */
    public static function getJoinableGroups(DatabaseInterface $db, int $userId): array
    {
        $subQuery = $db->getQuery(true)
            ->select($db->quoteName('group_id'))
            ->from($db->quoteName('#__livingword_group_members'))
            ->where($db->quoteName('user_id') . ' = ' . $userId);

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('g.id'),
                $db->quoteName('g.name'),
                $db->quoteName('g.start_date'),
                $db->quoteName('p.title', 'plan_title'),
                '(SELECT COUNT(*) FROM ' . $db->quoteName('#__livingword_group_members') . ' AS mc'
                    . ' WHERE mc.' . $db->quoteName('group_id') . ' = g.' . $db->quoteName('id')
                    . ') AS ' . $db->quoteName('member_count'),
            ])
            ->from($db->quoteName('#__livingword_groups', 'g'))
            ->join('LEFT', $db->quoteName('#__livingword_plans', 'p')
                . ' ON p.' . $db->quoteName('id') . ' = g.' . $db->quoteName('plan_id'))
            ->where($db->quoteName('g.published') . ' = 1')
            ->where($db->quoteName('g.id') . ' NOT IN (' . $subQuery . ')')
            ->order($db->quoteName('g.name') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Check if a user is a member of a group.
     *
     * @param   DatabaseInterface  $db       Database instance
     * @param   int                $groupId  Group ID
     * @param   int                $userId   Joomla user ID
     *
     * @return  bool
     *
     * @since   5.7.0
     */
    public static function isMember(DatabaseInterface $db, int $groupId, int $userId): bool
    {
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_group_members'))
            ->where($db->quoteName('group_id') . ' = ' . $groupId)
            ->where($db->quoteName('user_id') . ' = ' . $userId);

        $db->setQuery($query);

        return (int) $db->loadResult() > 0;
    }

    /**
     * Check if a user has the group_admin role in a group.
     *
     * @param   DatabaseInterface  $db       Database instance
     * @param   int                $groupId  Group ID
     * @param   int                $userId   Joomla user ID
     *
     * @return  bool
     *
     * @since   5.7.0
     */
    public static function isGroupAdmin(DatabaseInterface $db, int $groupId, int $userId): bool
    {
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_group_members'))
            ->where($db->quoteName('group_id') . ' = ' . $groupId)
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('role') . ' = ' . $db->quote('group_admin'));

        $db->setQuery($query);

        return (int) $db->loadResult() > 0;
    }

    /**
     * Check if a user can manage a group (group admin, component admin, or super admin).
     *
     * @param   DatabaseInterface  $db       Database instance
     * @param   int                $groupId  Group ID
     * @param   int                $userId   Joomla user ID
     *
     * @return  bool
     *
     * @throws \Exception
     * @since   5.7.0
     */
    public static function canManageGroup(DatabaseInterface $db, int $groupId, int $userId): bool
    {
        if (self::isGroupAdmin($db, $groupId, $userId)) {
            return true;
        }

        $user = Factory::getContainer()
            ->get(\Joomla\CMS\User\UserFactoryInterface::class)
            ->loadUserById($userId);

        if ($user->authorise('livingword.groups.admin', 'com_livingword')) {
            return true;
        }

        return $user->authorise('core.admin');
    }

    /**
     * Add a user to a group.
     *
     * @param   DatabaseInterface  $db       Database instance
     * @param   int                $groupId  Group ID
     * @param   int                $userId   Joomla user ID
     * @param   string             $role     Role: 'member' or 'group_admin'
     *
     * @return  bool  True on success
     *
     * @since   5.7.0
     */
    public static function joinGroup(
        DatabaseInterface $db,
        int $groupId,
        int $userId,
        string $role = 'member'
    ): bool {
        if (self::isMember($db, $groupId, $userId)) {
            return false;
        }

        $record = (object) [
            'group_id'  => $groupId,
            'user_id'   => $userId,
            'role'      => $role,
            'joined_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $db->insertObject('#__livingword_group_members', $record);
        } catch (\RuntimeException) {
            return false;
        }

        // Sync start date if user is on the same plan
        $query = $db->getQuery(true)
            ->select([$db->quoteName('plan_id'), $db->quoteName('start_date')])
            ->from($db->quoteName('#__livingword_groups'))
            ->where($db->quoteName('id') . ' = ' . $groupId);

        $db->setQuery($query);
        $group = $db->loadObject();

        if ($group) {
            self::syncStartDateIfSamePlan($db, $userId, (int) $group->plan_id, $group->start_date);
        }

        return true;
    }

    /**
     * Remove a user from a group.
     *
     * @param   DatabaseInterface  $db       Database instance
     * @param   int                $groupId  Group ID
     * @param   int                $userId   Joomla user ID
     *
     * @return  bool  True if a row was deleted
     *
     * @since   5.7.0
     */
    public static function leaveGroup(DatabaseInterface $db, int $groupId, int $userId): bool
    {
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__livingword_group_members'))
            ->where($db->quoteName('group_id') . ' = ' . $groupId)
            ->where($db->quoteName('user_id') . ' = ' . $userId);

        $db->setQuery($query);
        $db->execute();

        return $db->getAffectedRows() > 0;
    }

    /**
     * Get progress data for all members of a group.
     *
     * @param   DatabaseInterface  $db       Database instance
     * @param   int                $groupId  Group ID
     *
     * @return  array  Array of member progress objects
     *
     * @since   5.7.0
     */
    public static function getGroupMemberProgress(DatabaseInterface $db, int $groupId): array
    {
        // Get group info
        $query = $db->getQuery(true)
            ->select([$db->quoteName('plan_id'), $db->quoteName('start_date')])
            ->from($db->quoteName('#__livingword_groups'))
            ->where($db->quoteName('id') . ' = ' . $groupId);

        $db->setQuery($query);
        $group = $db->loadObject();

        if (!$group) {
            return [];
        }

        $planId     = (int) $group->plan_id;
        $startDate  = $group->start_date;
        $totalDays  = CwmreadingHelper::getPlanTotalDays($db, $planId);
        $currentDay = CwmreadingHelper::getCurrentReadingDay($startDate, 0, $totalDays ?: 365);

        // Get all members
        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('gm.user_id'),
                $db->quoteName('gm.role'),
                $db->quoteName('u.name'),
                $db->quoteName('lu.streak_current'),
            ])
            ->from($db->quoteName('#__livingword_group_members', 'gm'))
            ->join('INNER', $db->quoteName('#__users', 'u')
                . ' ON u.' . $db->quoteName('id') . ' = gm.' . $db->quoteName('user_id'))
            ->join('LEFT', $db->quoteName('#__livingword_users', 'lu')
                . ' ON lu.' . $db->quoteName('user_id') . ' = gm.' . $db->quoteName('user_id'))
            ->where($db->quoteName('gm.group_id') . ' = ' . $groupId)
            ->order($db->quoteName('u.name') . ' ASC');

        $db->setQuery($query);
        $members = $db->loadObjectList() ?: [];

        $result = [];

        foreach ($members as $member) {
            $completedCount  = CwmprogressHelper::getCompletedCount($db, (int) $member->user_id, $planId);
            $progressPercent = ($totalDays > 0) ? round(($completedCount / $totalDays) * 100) : 0;

            $result[] = (object) [
                'user_id'          => (int) $member->user_id,
                'name'             => $member->name,
                'role'             => $member->role,
                'current_day'      => $currentDay,
                'completed_count'  => $completedCount,
                'total_days'       => $totalDays,
                'progress_percent' => $progressPercent,
                'streak_current'   => (int) ($member->streak_current ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Sync user's start date if they are on the same plan as the group.
     *
     * @param   DatabaseInterface  $db              Database instance
     * @param   int                $userId          Joomla user ID
     * @param   int                $groupPlanId     The group's plan ID
     * @param   string             $groupStartDate  The group's start date
     *
     * @return  void
     *
     * @since   5.7.0
     */
    public static function syncStartDateIfSamePlan(
        DatabaseInterface $db,
        int $userId,
        int $groupPlanId,
        string $groupStartDate
    ): void {
        $query = $db->getQuery(true)
            ->select($db->quoteName('plan_id'))
            ->from($db->quoteName('#__livingword_users'))
            ->where($db->quoteName('user_id') . ' = ' . $userId);

        $db->setQuery($query);
        $userPlanId = (int) $db->loadResult();

        if ($userPlanId !== $groupPlanId || $userPlanId === 0) {
            return;
        }

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__livingword_users'))
            ->set($db->quoteName('start_date') . ' = ' . $db->quote($groupStartDate))
            ->set($db->quoteName('date_offset') . ' = 0')
            ->where($db->quoteName('user_id') . ' = ' . $userId);

        $db->setQuery($query);
        $db->execute();
    }
}
