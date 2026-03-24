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
                'username',
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

        $query->select(implode(', ', $db->quoteName([
            'a.id', 'a.user_id', 'a.plan_id', 'a.bible_version',
            'a.email', 'a.start_date',
        ])));
        $query->from($db->quoteName('#__livingword_users', 'a'));

        // Join users table for display name
        $query->select($db->quoteName('u.name', 'username'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.user_id'));

        // Join plans table for plan alias
        $query->select($db->quoteName('p.alias', 'plan_alias'))
            ->join('LEFT', $db->quoteName('#__livingword_plans', 'p') . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('a.plan_id'));

        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where('(' . $db->quoteName('u.name') . ' LIKE ' . $search . ')');
        }

        $orderCol  = $this->state->get('list.ordering', 'u.name');
        $orderDirn = $this->state->get('list.direction', 'asc');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
