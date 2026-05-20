<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Resources (links) model for site frontend.
 *
 * @since  5.0.0
 */
class CwmresourcesModel extends BaseDatabaseModel
{
    /**
     * Get published resource links grouped by category title.
     *
     * @return  array  Associative array keyed by category title
     *
     * @since   5.0.0
     */
    public function getResources(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName('a') . '.*')
            ->select($db->quoteName('c.title', 'category_title'))
            ->from($db->quoteName('#__livingword_links', 'a'))
            ->join(
                'LEFT',
                $db->quoteName('#__categories', 'c')
                    . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid')
            )
            ->where($db->quoteName('a.published') . ' = 1')
            ->order($db->quoteName('c.lft') . ' ASC, ' . $db->quoteName('a.ordering') . ' ASC');

        $db->setQuery($query);
        $rows = $db->loadObjectList() ?: [];

        $tagMap = $this->getTagsForLinks(array_column($rows, 'id'));

        $uncategorized = Text::_('COM_LIVINGWORD_UNCATEGORIZED');
        $grouped       = [];

        foreach ($rows as $row) {
            $row->tags = $tagMap[(int) $row->id] ?? [];

            $cat             = $row->category_title !== null && $row->category_title !== ''
                ? $row->category_title
                : $uncategorized;
            $grouped[$cat][] = $row;
        }

        return $grouped;
    }

    /**
     * Load published com_tags entries for the given link IDs in one query.
     *
     * @param   int[]  $linkIds  Link primary keys to look up
     *
     * @return  array<int, \stdClass[]>  Map of link id -> array of {id, title, alias}
     *
     * @since   5.6.0
     */
    private function getTagsForLinks(array $linkIds): array
    {
        $linkIds = array_filter(array_map('intval', $linkIds));

        if ($linkIds === []) {
            return [];
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName([
                'm.content_item_id',
                't.id',
                't.title',
                't.alias',
            ]))
            ->from($db->quoteName('#__contentitem_tag_map', 'm'))
            ->join(
                'INNER',
                $db->quoteName('#__tags', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('m.tag_id')
            )
            ->where($db->quoteName('m.type_alias') . ' = ' . $db->quote('com_livingword.link'))
            ->where($db->quoteName('m.content_item_id') . ' IN (' . implode(',', $linkIds) . ')')
            ->where($db->quoteName('t.published') . ' = 1')
            ->order($db->quoteName('t.title') . ' ASC');

        $rows = $db->setQuery($query)->loadObjectList() ?: [];

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row->content_item_id][] = $row;
        }

        return $out;
    }
}
