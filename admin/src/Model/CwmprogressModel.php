<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
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
 * Reading progress, always scoped to one user.
 *
 * Exists for the Web Services API: progress was previously reachable only
 * through CwmprogressHelper from the site controllers, and both ApiController
 * and JsonApiView require a model.
 *
 * **The user scope is applied here, not by callers.** getListQuery() always
 * constrains user_id, and the id comes from `filter.user_id` in the model
 * state — which the API controller sets from the authenticated session and
 * never from request input. A caller that forgets to set it gets no rows
 * rather than everyone's rows: the default is 0, which matches nobody.
 *
 * That default is the whole safety property. Progress is a record of what a
 * person has read and when; leaking it across users would be a privacy breach,
 * not a bug in a listing.
 *
 * @since  5.7.0
 */
class CwmprogressModel extends ListModel
{
    /**
     * @param   array  $config  Configuration settings.
     *
     * @throws  \Exception
     * @since   5.7.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'plan_id', 'a.plan_id',
                'day', 'a.day',
                'passage_index', 'a.passage_index',
                'completed_at', 'a.completed_at',
            ];
        }

        parent::__construct($config);
    }

    /**
     * @param   string  $ordering   Default ordering field.
     * @param   string  $direction  Default ordering direction.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   5.7.0
     */
    protected function populateState($ordering = 'a.completed_at', $direction = 'desc'): void
    {
        parent::populateState($ordering, $direction);
    }

    /**
     * Build the query, scoped to the user in state.
     *
     * @return  QueryInterface
     *
     * @since   5.7.0
     */
    protected function getListQuery(): QueryInterface
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select(
            $db->quoteName(['a.id', 'a.user_id', 'a.plan_id', 'a.day', 'a.passage_index', 'a.completed_at'])
        )->from($db->quoteName('#__livingword_progress', 'a'));

        // Defaults to 0 — matches no user — so a caller that fails to set the
        // scope reads nothing instead of reading everything.
        $userId = (int) $this->getState('filter.user_id', 0);
        $query->where($db->quoteName('a.user_id') . ' = :userId')
            ->bind(':userId', $userId, \Joomla\Database\ParameterType::INTEGER);

        $planId = (int) $this->getState('filter.plan_id', 0);

        if ($planId > 0) {
            $query->where($db->quoteName('a.plan_id') . ' = :planId')
                ->bind(':planId', $planId, \Joomla\Database\ParameterType::INTEGER);
        }

        $day = $this->getState('filter.day');

        if (is_numeric($day)) {
            $dayValue = (int) $day;
            $query->where($db->quoteName('a.day') . ' = :day')
                ->bind(':day', $dayValue, \Joomla\Database\ParameterType::INTEGER);
        }

        $orderCol  = $this->state->get('list.ordering', 'a.completed_at');
        $orderDirn = $this->state->get('list.direction', 'desc');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
