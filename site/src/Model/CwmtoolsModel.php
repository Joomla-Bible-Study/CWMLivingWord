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
     * Get published study tools ordered by ordering.
     *
     * @return  array
     *
     * @since   5.4.0
     */
    public function getTools(): array
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select($db->quoteName(['id', 'name', 'description', 'url', 'icon', 'color']))
            ->from($db->quoteName('#__livingword_tools'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);

        return $db->loadAssocList() ?: [];
    }
}
