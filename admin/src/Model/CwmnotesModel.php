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
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

/**
 * Per-day reflection notes, always scoped to one user.
 *
 * The same scoping contract as CwmprogressModel: getListQuery() constrains
 * user_id from `filter.user_id`, defaulting to 0 so a caller that forgets the
 * scope reads nothing rather than everything.
 *
 * Notes are the most private data the component holds — a person's written
 * reflections on scripture. Nothing here should ever be readable by another
 * user, including a site administrator through this model.
 *
 * @since  5.7.0
 */
class CwmnotesModel extends ListModel
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
                'modified', 'a.modified',
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
    protected function populateState($ordering = 'a.modified', $direction = 'desc'): void
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
            $db->quoteName(['a.id', 'a.user_id', 'a.plan_id', 'a.day', 'a.note_text', 'a.created', 'a.modified'])
        )->from($db->quoteName('#__livingword_notes', 'a'));

        $userId = (int) $this->getState('filter.user_id', 0);
        $query->where($db->quoteName('a.user_id') . ' = :userId')
            ->bind(':userId', $userId, ParameterType::INTEGER);

        $planId = (int) $this->getState('filter.plan_id', 0);

        if ($planId > 0) {
            $query->where($db->quoteName('a.plan_id') . ' = :planId')
                ->bind(':planId', $planId, ParameterType::INTEGER);
        }

        $day = $this->getState('filter.day');

        if (is_numeric($day)) {
            $dayValue = (int) $day;
            $query->where($db->quoteName('a.day') . ' = :day')
                ->bind(':day', $dayValue, ParameterType::INTEGER);
        }

        $orderCol  = $this->state->get('list.ordering', 'a.modified');
        $orderDirn = $this->state->get('list.direction', 'desc');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
