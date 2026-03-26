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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;

/**
 * Users/subscribers list model
 *
 * @since  5.0.0
 */
class CwmusersModel extends ListModel
{
    /**
     * @param   array  $config  Configuration settings.
     *
     * @throws \Exception
     * @since 5.0.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'user_id', 'a.user_id',
                'plan_id', 'a.plan_id',
                'bible_version', 'a.bible_version',
                'email', 'a.email',
                'username', 'u.name',
                'streak_current', 'a.streak_current',
                'progress',
            ];
        }

        parent::__construct($config);
    }

    /**
     * @param   string  $ordering   Default ordering field.
     * @param   string  $direction  Default direction.
     *
     * @return  void
     *
     * @since   5.0.0
     */
    protected function populateState($ordering = 'u.name', $direction = 'ASC'): void
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '');
        $this->setState('filter.search', $search);

        $plan = $this->getUserStateFromRequest($this->context . '.filter.plan_id', 'filter_plan_id', '');
        $this->setState('filter.plan_id', $plan);

        $emailFilter = $this->getUserStateFromRequest($this->context . '.filter.email', 'filter_email', '');
        $this->setState('filter.email', $emailFilter);

        parent::populateState($ordering, $direction);
    }

    /**
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string
     *
     * @since   5.0.0
     */
    protected function getStoreId($id = ''): string
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.plan_id');
        $id .= ':' . $this->getState('filter.email');

        return parent::getStoreId($id);
    }

    /**
     * @return  QueryInterface
     *
     * @since   5.0.0
     */
    protected function getListQuery(): QueryInterface
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select($db->quoteName([
            'a.id', 'a.user_id', 'a.plan_id', 'a.bible_version',
            'a.email', 'a.start_date', 'a.streak_current', 'a.streak_best',
            'a.streak_last_date', 'a.date_offset',
        ]));
        $query->from($db->quoteName('#__livingword_users', 'a'));

        // Join users table for display name and email
        $query->select([
            $db->quoteName('u.name', 'username'),
            $db->quoteName('u.email', 'user_email'),
        ])
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.user_id'));

        // Join plans table for plan title
        $query->select($db->quoteName('p.title', 'plan_title'))
            ->join('LEFT', $db->quoteName('#__livingword_plans', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('a.plan_id'));

        // Subquery: completed reading count
        $progressSub = $db->getQuery(true)
            ->select('COUNT(DISTINCT ' . $db->quoteName('day') . ')')
            ->from($db->quoteName('#__livingword_progress'))
            ->where($db->quoteName('user_id') . ' = ' . $db->quoteName('a.user_id'))
            ->where($db->quoteName('plan_id') . ' = ' . $db->quoteName('a.plan_id'));
        $query->select('(' . $progressSub . ') AS ' . $db->quoteName('completed_count'));

        // Subquery: total readings in plan
        $totalSub = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_plans_details'))
            ->where($db->quoteName('plan_id') . ' = ' . $db->quoteName('a.plan_id'));
        $query->select('(' . $totalSub . ') AS ' . $db->quoteName('total_days'));

        // Filter: search by name or email
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where('(' . $db->quoteName('u.name') . ' LIKE ' . $search
                . ' OR ' . $db->quoteName('u.email') . ' LIKE ' . $search . ')');
        }

        // Filter: plan
        $planFilter = (int) $this->getState('filter.plan_id');

        if ($planFilter > 0) {
            $query->where($db->quoteName('a.plan_id') . ' = ' . $planFilter);
        }

        // Filter: email subscribed
        $emailFilter = $this->getState('filter.email');

        if ($emailFilter !== '' && $emailFilter !== null) {
            $query->where($db->quoteName('a.email') . ' = ' . (int) $emailFilter);
        }

        $orderCol  = $this->state->get('list.ordering', 'u.name');
        $orderDirn = $this->state->get('list.direction', 'asc');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
