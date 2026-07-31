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
 * Plan details (readings) list model
 *
 * @since  5.0.0
 */
class CwmplandetailsModel extends ListModel
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
                'plan', 'p.title',
                'plan_id', 'a.plan_id',
                'reading', 'a.reading',
                'ordering', 'a.ordering',
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
    protected function populateState($ordering = 'a.ordering', $direction = 'ASC'): void
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '');
        $this->setState('filter.search', $search);

        $plan = $this->getUserStateFromRequest($this->context . '.filter.plan', 'filter_plan', '');
        $this->setState('filter.plan', $plan);

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
        $id .= ':' . $this->getState('filter.plan');

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

        // Columns are those #__livingword_plans_details actually has. The list
        // previously selected a.plan, a.figure, a.checked_out and
        // a.checked_out_time — none of which exist on the table — so every
        // request to this view ended in "Unknown column 'a.plan' in 'field
        // list'" and a 500. There is no check-out support on plan days, so the
        // editor join goes with them.
        $query->select(
            $this->getState(
                'list.select',
                implode(', ', $db->quoteName([
                    'a.id', 'a.plan_id', 'a.reading', 'a.audio', 'a.descrip', 'a.ordering',
                ]))
            )
        );
        $query->from($db->quoteName('#__livingword_plans_details', 'a'));

        // The list shows the plan a day belongs to, and the table stores only
        // its id. Aliased to `plan` because that is what the template renders,
        // and a title is more use there than a number.
        $query->select($db->quoteName('p.title', 'plan'))
            ->join(
                'LEFT',
                $db->quoteName('#__livingword_plans', 'p')
                . ' ON ' . $db->quoteName('p.id') . ' = ' . $db->quoteName('a.plan_id')
            );

        // Filter by plan. The filter is a free-text field, so it matches the
        // plan title rather than requiring an id the user cannot see.
        $plan = $this->getState('filter.plan');

        if (!empty($plan)) {
            $query->where(
                $db->quoteName('p.title') . ' LIKE ' . $db->quote('%' . $db->escape($plan, true) . '%')
            );
        }

        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 3));
            } else {
                $search = $db->quote('%' . $db->escape($search, true) . '%');
                $query->where('(' . $db->quoteName('a.reading') . ' LIKE ' . $search . ')');
            }
        }

        $orderCol  = $this->state->get('list.ordering', 'a.ordering');
        $orderDirn = $this->state->get('list.direction', 'asc');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
