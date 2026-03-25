<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Dashboard/control panel model with congregation stats.
 *
 * @since  5.0.0
 */
class CwmcpanelModel extends BaseDatabaseModel
{
    /**
     * Get counts for dashboard quick icons.
     *
     * @return  array  Associative array of counts
     *
     * @since   5.0.0
     */
    public function getCounts(): array
    {
        $db = $this->getDatabase();

        $counts = [];

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_plans'))
            ->where($db->quoteName('published') . ' = 1');
        $db->setQuery($query);
        $counts['plans'] = (int) $db->loadResult();

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_links'))
            ->where($db->quoteName('published') . ' = 1');
        $db->setQuery($query);
        $counts['links'] = (int) $db->loadResult();

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_users'));
        $db->setQuery($query);
        $counts['users'] = (int) $db->loadResult();

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_groups'))
            ->where($db->quoteName('published') . ' = 1');
        $db->setQuery($query);
        $counts['groups'] = (int) $db->loadResult();

        return $counts;
    }

    /**
     * Get congregation health statistics.
     *
     * @return  object  Stats object with subscribers, active, inactive, plans, groups
     *
     * @since   5.7.0
     */
    public function getStats(): object
    {
        $db = $this->getDatabase();

        // Total subscribers
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_users'));
        $db->setQuery($query);
        $totalSubscribers = (int) $db->loadResult();

        // Active last 7 days (have a progress record in the last 7 days)
        $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
        $query        = $db->getQuery(true)
            ->select('COUNT(DISTINCT ' . $db->quoteName('user_id') . ')')
            ->from($db->quoteName('#__livingword_progress'))
            ->where($db->quoteName('completed_at') . ' >= ' . $db->quote($sevenDaysAgo));
        $db->setQuery($query);
        $activeUsers = (int) $db->loadResult();

        $inactiveUsers = max($totalSubscribers - $activeUsers, 0);

        // Plan enrollment breakdown
        $query = $db->getQuery(true)
            ->select('p.title, COUNT(u.id) AS user_count')
            ->from($db->quoteName('#__livingword_users', 'u'))
            ->join('LEFT', $db->quoteName('#__livingword_plans', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('u.plan_id'))
            ->group($db->quoteName('u.plan_id'))
            ->order('user_count DESC');
        $db->setQuery($query);
        $planEnrollment = $db->loadObjectList() ?: [];

        // Average progress per plan
        $query = $db->getQuery(true)
            ->select('p.id, p.title')
            ->select('COUNT(DISTINCT u.user_id) AS user_count')
            ->select('COUNT(DISTINCT pr.day) AS avg_completed_days')
            ->from($db->quoteName('#__livingword_plans', 'p'))
            ->join('INNER', $db->quoteName('#__livingword_users', 'u') . ' ON ' . $db->quoteName('u.plan_id') . ' = ' . $db->quoteName('p.id'))
            ->join('LEFT', $db->quoteName('#__livingword_progress', 'pr') . ' ON ' . $db->quoteName('pr.user_id') . ' = ' . $db->quoteName('u.user_id') . ' AND ' . $db->quoteName('pr.plan_id') . ' = ' . $db->quoteName('p.id'))
            ->where($db->quoteName('p.published') . ' = 1')
            ->group($db->quoteName('p.id'))
            ->order($db->quoteName('p.ordering') . ' ASC');
        $db->setQuery($query);
        $planProgressRaw = $db->loadObjectList() ?: [];

        // Get total days per plan for percentage calculation
        $planProgress = [];

        foreach ($planProgressRaw as $row) {
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__livingword_plans_details'))
                ->where($db->quoteName('plan_id') . ' = ' . (int) $row->id);
            $db->setQuery($query);
            $totalDays = (int) $db->loadResult();

            $avgCompleted = ($row->user_count > 0)
                ? round((int) $row->avg_completed_days / (int) $row->user_count)
                : 0;

            $avgPercent = ($totalDays > 0) ? round(($avgCompleted / $totalDays) * 100) : 0;

            $planProgress[] = (object) [
                'title'         => $row->title,
                'user_count'    => (int) $row->user_count,
                'total_days'    => $totalDays,
                'avg_completed' => $avgCompleted,
                'avg_percent'   => min($avgPercent, 100),
            ];
        }

        // Inactive users (no progress in 7+ days, with names)
        $query = $db->getQuery(true)
            ->select('ju.name, lu.streak_last_date, lu.plan_id')
            ->from($db->quoteName('#__livingword_users', 'lu'))
            ->join('INNER', $db->quoteName('#__users', 'ju') . ' ON ' . $db->quoteName('ju.id') . ' = ' . $db->quoteName('lu.user_id'))
            ->where('(' . $db->quoteName('lu.streak_last_date') . ' IS NULL OR ' . $db->quoteName('lu.streak_last_date') . ' < ' . $db->quote(date('Y-m-d', strtotime('-7 days'))) . ')')
            ->order($db->quoteName('lu.streak_last_date') . ' ASC')
            ->setLimit(20);
        $db->setQuery($query);
        $inactiveList = $db->loadObjectList() ?: [];

        // Group summaries
        $query = $db->getQuery(true)
            ->select('g.id, g.name, g.plan_id, g.start_date, p.title AS plan_title')
            ->select('(SELECT COUNT(*) FROM ' . $db->quoteName('#__livingword_group_members') . ' gm WHERE gm.group_id = g.id) AS member_count')
            ->from($db->quoteName('#__livingword_groups', 'g'))
            ->join('LEFT', $db->quoteName('#__livingword_plans', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('g.plan_id'))
            ->where($db->quoteName('g.published') . ' = 1')
            ->order($db->quoteName('g.ordering') . ' ASC');
        $db->setQuery($query);
        $groups = $db->loadObjectList() ?: [];

        // For each group, compute average progress
        foreach ($groups as $group) {
            $query = $db->getQuery(true)
                ->select('AVG(sub.completed_count) AS avg_completed')
                ->select('AVG(lu.streak_current) AS avg_streak')
                ->from('(SELECT pr.user_id, COUNT(DISTINCT pr.day) AS completed_count'
                    . ' FROM ' . $db->quoteName('#__livingword_progress') . ' pr'
                    . ' INNER JOIN ' . $db->quoteName('#__livingword_group_members') . ' gm ON gm.user_id = pr.user_id'
                    . ' WHERE gm.group_id = ' . (int) $group->id
                    . ' AND pr.plan_id = ' . (int) $group->plan_id
                    . ' GROUP BY pr.user_id) AS sub')
                ->join('LEFT', $db->quoteName('#__livingword_users', 'lu') . ' ON ' . $db->quoteName('lu.user_id') . ' = sub.user_id');
            $db->setQuery($query);
            $row = $db->loadObject();

            $totalDays = 0;

            if ((int) $group->plan_id > 0) {
                $query = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->quoteName('#__livingword_plans_details'))
                    ->where($db->quoteName('plan_id') . ' = ' . (int) $group->plan_id);
                $db->setQuery($query);
                $totalDays = (int) $db->loadResult();
            }

            $avgCompleted            = (int) round((float) ($row->avg_completed ?? 0));
            $group->avg_progress     = ($totalDays > 0) ? min(round(($avgCompleted / $totalDays) * 100), 100) : 0;
            $group->avg_streak       = (int) round((float) ($row->avg_streak ?? 0));
            $group->total_days       = $totalDays;
        }

        // Audio-enabled plans count
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_plans'))
            ->where($db->quoteName('audio') . ' = 1')
            ->where($db->quoteName('published') . ' = 1');
        $db->setQuery($query);
        $audioPlansCount = (int) $db->loadResult();

        return (object) [
            'totalSubscribers' => $totalSubscribers,
            'activeUsers'      => $activeUsers,
            'inactiveUsers'    => $inactiveUsers,
            'planEnrollment'   => $planEnrollment,
            'planProgress'     => $planProgress,
            'inactiveList'     => $inactiveList,
            'groups'           => $groups,
            'audioPlansCount'  => $audioPlansCount,
        ];
    }
}
