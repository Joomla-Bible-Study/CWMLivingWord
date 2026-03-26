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

use CWM\Component\Livingword\Site\Helper\CwmnotesHelper;
use CWM\Component\Livingword\Site\Helper\CwmpartnerHelper;
use CWM\Component\Livingword\Site\Helper\CwmprogressHelper;
use CWM\Component\Livingword\Site\Helper\CwmreadingHelper;
use CWM\Component\Livingword\Site\Helper\CwmuserHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Home/main view model — user settings and today's reading.
 *
 * @since  5.0.0
 */
class CwmhomeModel extends BaseDatabaseModel
{
    /**
     * Get user data and today's reading.
     *
     * @return  object  Object with userData, todayReading, planInfo
     *
     * @throws \Exception
     * @since   5.0.0
     */
    public function getHomeData(): object
    {
        $db     = $this->getDatabase();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        $userData = CwmuserHelper::getUserData($db, $userId);
        $planId   = (int) $userData->plan_id;

        $totalDays = CwmreadingHelper::getPlanTotalDays($db, $planId);
        $planInfo  = CwmreadingHelper::getPlanById($db, $planId);

        $currentDay = CwmreadingHelper::getReadingDayForPlan(
            $planInfo,
            $userData->start_date ?? '',
            (int) $userData->date_offset,
            $totalDays ?: 365,
            $db,
            $userId
        );

        $todayReading = ($currentDay > 0) ? CwmreadingHelper::getReadingForDay($db, $planId, $currentDay) : null;

        $isCompleted         = false;
        $completedCount      = 0;
        $passages            = [];
        $completedPassages   = [];
        $passageCount        = 1;

        if ($todayReading) {
            $passages     = CwmprogressHelper::splitPassages($todayReading->reading);
            $passageCount = \count($passages);
        }

        if ($userId > 0) {
            $isCompleted       = CwmprogressHelper::isCompleted($db, $userId, $planId, $currentDay, $passageCount);
            $completedCount    = CwmprogressHelper::getCompletedCount($db, $userId, $planId);
            $completedPassages = CwmprogressHelper::getCompletedPassages($db, $userId, $planId, $currentDay);
        }

        $progressPercent = ($totalDays > 0) ? round(($completedCount / $totalDays) * 100) : 0;

        $partnerProgress = null;
        $todayNote       = '';

        if ($userId > 0) {
            $partnerProgress = CwmpartnerHelper::getPartnerProgress($db, $userId);
            $todayNote       = CwmnotesHelper::getNote($db, $userId, $planId, $currentDay) ?? '';
        }

        return (object) [
            'userData'          => $userData,
            'todayReading'      => $todayReading,
            'planInfo'          => $planInfo,
            'currentDay'        => $currentDay,
            'totalDays'         => $totalDays,
            'isCompleted'       => $isCompleted,
            'completedCount'    => $completedCount,
            'progressPercent'   => $progressPercent,
            'passages'          => $passages,
            'passageCount'      => $passageCount,
            'completedPassages' => $completedPassages,
            'partnerProgress'   => $partnerProgress,
            'durationType'      => $planInfo->duration_type ?? 'annual',
            'todayNote'         => $todayNote,
        ];
    }
}
