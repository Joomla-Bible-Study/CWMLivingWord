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

use CWM\Component\Livingword\Site\Helper\CwmprogressHelper;
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

        $userData   = CwmuserHelper::getUserData($db, $userId);
        $planId     = (int) $userData->plan_id;
        $planInfo   = CwmreadingHelper::getPlanById($db, $planId);
        $readings   = CwmreadingHelper::getAllReadings($db, $planId);
        $totalDays  = \count($readings);
        $currentDay = CwmreadingHelper::getReadingDayForPlan(
            $planInfo,
            $userData->start_date ?? '',
            (int) $userData->date_offset,
            $totalDays ?: 365,
            $db,
            $userId
        );

        $completedDays          = [];
        $completedPassageCounts = [];

        if ($userId > 0) {
            $completedDays          = CwmprogressHelper::getCompletedDays($db, $userId, $planId);
            $completedPassageCounts = CwmprogressHelper::getCompletedPassageCounts($db, $userId, $planId);
        }

        $completedCount  = \count($completedDays);
        $progressPercent = ($totalDays > 0) ? round(($completedCount / $totalDays) * 100) : 0;

        return (object) [
            'planInfo'               => $planInfo,
            'readings'               => $readings,
            'userData'               => $userData,
            'currentDay'             => $currentDay,
            'totalDays'              => $totalDays,
            'completedDays'          => $completedDays,
            'completedCount'         => $completedCount,
            'progressPercent'        => $progressPercent,
            'completedPassageCounts' => $completedPassageCounts,
        ];
    }
}
