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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;

/**
 * Tools model for Bible study tools page.
 *
 * @since  5.0.0
 */
class CwmtoolsModel extends BaseDatabaseModel
{
    /**
     * Get published study tools grouped by category.
     *
     * @return  array  Associative array keyed by category title, each containing an array of tools.
     *
     * @since   5.4.0
     */
    public function getTools(): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select($db->quoteName([
            'a.id', 'a.name', 'a.description', 'a.url', 'a.icon', 'a.color',
        ]));
        $query->select($db->quoteName('c.title', 'category_title'));
        $query->from($db->quoteName('#__livingword_tools', 'a'));
        $query->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.catid'));
        $query->where($db->quoteName('a.published') . ' = 1');
        $query->order($db->quoteName('c.lft') . ' ASC, ' . $db->quoteName('a.ordering') . ' ASC');

        $db->setQuery($query);
        $rows = $db->loadAssocList() ?: [];

        $grouped = [];

        foreach ($rows as $row) {
            $cat             = $row['category_title'] ?: '';
            $grouped[$cat][] = $row;
        }

        return $grouped;
    }
}
