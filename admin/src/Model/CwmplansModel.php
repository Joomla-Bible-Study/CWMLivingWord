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
 * Plans list model
 *
 * @since  5.0.0
 */
class CwmplansModel extends ListModel
{
    /**
     * @param   array  $config  An optional associative array of configuration settings.
     *
     * @throws \Exception
     * @since 5.0.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'a.id',
                'alias', 'a.alias',
                'title', 'a.title',
                'description', 'a.description',
                'audio', 'a.audio',
                'testament', 'a.testament',
                'published', 'a.published',
                'ordering', 'a.ordering',
                'tag',
            ];
        }

        parent::__construct($config);
    }

    /**
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     * @since   5.0.0
     */
    protected function populateState($ordering = 'a.ordering', $direction = 'ASC'): void
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '');
        $this->setState('filter.search', $search);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        $tag = $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag', '');
        $this->setState('filter.tag', $tag);

        parent::populateState($ordering, $direction);
    }

    /**
     * @param   string  $id  A prefix for the store id
     *
     * @return  string
     *
     * @since   5.0.0
     */
    protected function getStoreId($id = ''): string
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . serialize($this->getState('filter.tag'));

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

        $query->select(
            $this->getState(
                'list.select',
                implode(', ', $db->quoteName([
                    'a.id', 'a.alias', 'a.title', 'a.description', 'a.message', 'a.audio',
                    'a.testament', 'a.published', 'a.checked_out', 'a.checked_out_time', 'a.ordering',
                ]))
            )
        );
        $query->from($db->quoteName('#__livingword_plans', 'a'));

        $query->select($db->quoteName('uc.name', 'editor'))
            ->join('LEFT', $db->quoteName('#__users', 'uc') . ' ON ' . $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

        // Filter by search
        $search = $this->getState('filter.search');

        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('a.id') . ' = ' . (int) substr($search, 3));
            } else {
                $search = $db->quote('%' . $db->escape($search, true) . '%');
                $query->where('(' . $db->quoteName('a.alias') . ' LIKE ' . $search
                    . ' OR ' . $db->quoteName('a.title') . ' LIKE ' . $search
                    . ' OR ' . $db->quoteName('a.description') . ' LIKE ' . $search . ')');
            }
        }

        // Filter by published state
        $published = $this->getState('filter.published');

        if (is_numeric($published)) {
            $query->where($db->quoteName('a.published') . ' = ' . (int) $published);
        } elseif ($published === '') {
            $query->where($db->quoteName('a.published') . ' IN (0, 1)');
        }

        // Filter by tag (com_tags integration)
        $tagId = $this->getState('filter.tag');

        if (!empty($tagId) && (is_numeric($tagId) || \is_array($tagId))) {
            $tagIds = array_filter(array_map('intval', (array) $tagId));

            if (!empty($tagIds)) {
                $query->join(
                    'INNER',
                    $db->quoteName('#__contentitem_tag_map', 'tagmap')
                    . ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
                    . ' AND ' . $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_livingword.plan')
                );
                $query->where($db->quoteName('tagmap.tag_id') . ' IN (' . implode(',', $tagIds) . ')');
            }
        }

        $orderCol  = $this->state->get('list.ordering', 'a.ordering');
        $orderDirn = $this->state->get('list.direction', 'asc');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
