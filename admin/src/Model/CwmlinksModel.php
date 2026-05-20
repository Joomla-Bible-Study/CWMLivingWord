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
 * Links list model
 *
 * @since  5.0.0
 */
class CwmlinksModel extends ListModel
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
                'name', 'a.name',
                'url', 'a.url',
                'catid', 'a.catid',
                'category_title', 'c.title',
                'published', 'a.published',
                'ordering', 'a.ordering',
                'tag',
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
    protected function populateState($ordering = 'c.title, a.ordering', $direction = 'ASC'): void
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '');
        $this->setState('filter.search', $search);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        $catid = $this->getUserStateFromRequest($this->context . '.filter.catid', 'filter_catid', '');
        $this->setState('filter.catid', $catid);

        $tag = $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag', '');
        $this->setState('filter.tag', $tag);

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
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.catid');
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
                    'a.id', 'a.name', 'a.url', 'a.catid', 'a.target',
                    'a.published', 'a.checked_out', 'a.checked_out_time', 'a.ordering',
                ]))
            )
        );
        $query->from($db->quoteName('#__livingword_links', 'a'));

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

        // Filter by tag (com_tags integration)
        $tagId = $this->getState('filter.tag');

        if (!empty($tagId) && (is_numeric($tagId) || \is_array($tagId))) {
            $tagIds = array_filter(array_map('intval', (array) $tagId));

            if (!empty($tagIds)) {
                $query->join(
                    'INNER',
                    $db->quoteName('#__contentitem_tag_map', 'tagmap')
                    . ' ON ' . $db->quoteName('tagmap.content_item_id') . ' = ' . $db->quoteName('a.id')
                    . ' AND ' . $db->quoteName('tagmap.type_alias') . ' = ' . $db->quote('com_livingword.link')
                );
                $query->where($db->quoteName('tagmap.tag_id') . ' IN (' . implode(',', $tagIds) . ')');
            }
        }

        $orderCol  = $this->state->get('list.ordering', 'c.title, a.ordering');
        $orderDirn = $this->state->get('list.direction', 'asc');
        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));

        return $query;
    }
}
