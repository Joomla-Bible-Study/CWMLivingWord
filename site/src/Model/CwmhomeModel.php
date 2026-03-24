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

        $totalDays    = CwmreadingHelper::getPlanTotalDays($db, $planId);
        $currentDay   = CwmreadingHelper::getCurrentReadingDay($userData->start_date ?? '', (int) $userData->date_offset, $totalDays ?: 365);
        $todayReading = CwmreadingHelper::getReadingForDay($db, $planId, $currentDay);
        $planInfo     = CwmreadingHelper::getPlanById($db, $planId);

        $isCompleted = ($userId > 0)
            ? CwmprogressHelper::isCompleted($db, $userId, $planId, $currentDay)
            : false;

        return (object) [
            'userData'     => $userData,
            'todayReading' => $todayReading,
            'planInfo'     => $planInfo,
            'currentDay'   => $currentDay,
            'totalDays'    => $totalDays,
            'isCompleted'  => $isCompleted,
        ];
    }
}
