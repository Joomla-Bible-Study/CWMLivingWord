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

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Database\DatabaseInterface;

/**
 * Dashboard/control panel model
 *
 * @since  5.0.0
 */
class CwmcpanelModel extends BaseDatabaseModel
{
    /**
     * Get counts for dashboard quick icons.
     *
     * @return  array  Associative array of counts
     *
     * @since   5.0.0
     */
    public function getCounts(): array
    {
        $db = $this->getDatabase();

        $counts = [];

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_plans'))
            ->where($db->quoteName('published') . ' = 1');
        $db->setQuery($query);
        $counts['plans'] = (int) $db->loadResult();

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_links'))
            ->where($db->quoteName('published') . ' = 1');
        $db->setQuery($query);
        $counts['links'] = (int) $db->loadResult();

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_users'));
        $db->setQuery($query);
        $counts['users'] = (int) $db->loadResult();

        return $counts;
    }
}
