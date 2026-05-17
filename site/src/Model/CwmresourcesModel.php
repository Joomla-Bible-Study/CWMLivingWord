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
        $rows = $db->loadObjectList();

        $uncategorized = Text::_('COM_LIVINGWORD_UNCATEGORIZED');
        $grouped       = [];

        foreach ($rows as $row) {
            $cat             = $row->category_title !== null && $row->category_title !== ''
                ? $row->category_title
                : $uncategorized;
            $grouped[$cat][] = $row;
        }

        return $grouped;
    }
}
