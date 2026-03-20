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

use CWM\Component\Livingword\Site\Helper\CwmreadingHelper;
use CWM\Component\Livingword\Site\Helper\CwmuserHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Full plan view model.
 *
 * @since  5.0.0
 */
class CwmplanviewModel extends BaseDatabaseModel
{
    /**
     * Get full plan data for display.
     *
     * @return  object  Object with planInfo, readings, userData, currentDay
     *
     * @throws \Exception
     * @since   5.0.0
     */
    public function getPlanViewData(): object
    {
        $db     = $this->getDatabase();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        $userData  = CwmuserHelper::getUserData($db, $userId);
        $planInfo  = CwmreadingHelper::getPlanByName($db, $userData->bibleplan);
        $readings  = CwmreadingHelper::getAllReadings($db, $userData->bibleplan);
        $totalDays = \count($readings);
        $currentDay = CwmreadingHelper::getCurrentReadingDay(
            $userData->startdate,
            (int) $userData->dateoffset,
            $totalDays ?: 365
        );

        return (object) [
            'planInfo'   => $planInfo,
            'readings'   => $readings,
            'userData'   => $userData,
            'currentDay' => $currentDay,
            'totalDays'  => $totalDays,
        ];
    }
}
