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

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Resources (links) model for site frontend.
 *
 * @since  5.0.0
 */
class CwmresourcesModel extends BaseDatabaseModel
{
    /**
     * Get published resource links grouped by category.
     *
     * @return  array  Associative array keyed by category name
     *
     * @since   5.0.0
     */
    public function getResources(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__livingword_links'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('category') . ' ASC, ' . $db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);
        $rows = $db->loadObjectList();

        $grouped = [];

        foreach ($rows as $row) {
            $cat = $row->category ?: 'Uncategorized';
            $grouped[$cat][] = $row;
        }

        return $grouped;
    }
}
