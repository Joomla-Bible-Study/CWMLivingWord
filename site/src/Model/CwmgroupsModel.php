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

use CWM\Component\Livingword\Site\Helper\CwmgroupHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Groups list model for site frontend.
 *
 * @since  5.7.0
 */
class CwmgroupsModel extends BaseDatabaseModel
{
    /**
     * Get groups the current user belongs to.
     *
     * @return  array  Array of group objects
     *
     * @throws \Exception
     * @since   5.7.0
     */
    public function getMyGroups(): array
    {
        $db     = $this->getDatabase();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        return CwmgroupHelper::getUserGroups($db, $userId);
    }

    /**
     * Get published groups the current user can join.
     *
     * @return  array  Array of group objects
     *
     * @throws \Exception
     * @since   5.7.0
     */
    public function getJoinableGroups(): array
    {
        $db     = $this->getDatabase();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        return CwmgroupHelper::getJoinableGroups($db, $userId);
    }
}
