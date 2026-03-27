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
 * Tools list model
 *
 * @since  5.4.0
 */
class CwmtoolsModel extends ListModel
{
    /**
     * @param   array  $config  Configuration settings.
     *
     * @throws \Exception
     * @since 5.4.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'name', 'a.name',
                'catid', 'a.catid',
                'category_title', 'c.title',
                'published', 'a.published',
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
     * @since   5.4.0
     */
    protected function populateState($ordering = 'c.title, a.ordering', $direction = 'ASC'): void
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '');
        $this->setState('filter.search', $search);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        $catid = $this->getUserStateFromRequest($this->context . '.filter.catid', 'filter_catid', '');
        $this->setState('filter.catid', $catid);

        parent::populateState($ordering, $direction);
    }

    /**
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string
     *
     * @since   5.4.0
     */
    protected function getStoreId($id = ''): string
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.catid');

        return parent::getStoreId($id);
    }

    /**
     * @return  QueryInterface
     *
     * @since   5.4.0
     */
    protected function getListQuery(): QueryInterface
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select(
            $this->getState(
                'list.select',
                implode(', ', $db->quoteName([
                    'a.id', 'a.name', 'a.description', 'a.url', 'a.icon', 'a.color',
                    'a.catid', 'a.published', 'a.checked_out', 'a.checked_out_time', 'a.ordering',
                ]))
            )
        );
        $query->from($db->quoteName('#__livingword_tools', 'a'));

        $query->select($db->quoteName('c.title', 'category_title'))
            ->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'));

        $query->select($db->quoteName('uc.name', 'editor'))
            ->join('LEFT', $db->quoteName('#__users', 'uc') . ' ON ' . $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 3));
            } else {
                $search = $db->quote('%' . $db->escape($search, true) . '%');
                $query->where('(' . $db->quoteName('a.name') . ' LIKE ' . $search . ')');
            }
        }

        $published = $this->getState('filter.published');

        if (is_numeric($published)) {
            $query->where($db->quoteName('a.published') . ' = ' . (int) $published);
        } elseif ($published === '') {
            $query->where($db->quoteName('a.published') . ' IN (0, 1)');
        }

        $catid = $this->getState('filter.catid');

        if (is_numeric($catid)) {
            $query->where($db->quoteName('a.catid') . ' = ' . (int) $catid);
        }

        $orderCol  = $this->state->get('list.ordering', 'a.ordering');
        $orderDirn = $this->state->get('list.direction', 'asc');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
