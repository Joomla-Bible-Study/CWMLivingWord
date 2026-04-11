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
use Joomla\Database\DatabaseInterface;

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

        $userData      = CwmuserHelper::getUserData($db, $userId);
        $isSubscribed  = (bool) ($userData->is_subscribed ?? false);
        $planId        = $isSubscribed ? (int) $userData->plan_id : 0;

        // Users without a real #__livingword_users row (guests and
        // logged-in users who've never picked a plan) get the onboarding
        // view — no progress, no today's-reading logic, just the plan
        // picker.  Skip all the per-plan computation for them.
        if (!$isSubscribed) {
            $availablePlans  = self::getAvailablePlans($db);
            $greetingContext = $userId > 0 ? 'unsubscribed' : 'guest_unsubscribed';

            return (object) [
                'userData'          => $userData,
                'todayReading'      => null,
                'planInfo'          => null,
                'currentDay'        => 0,
                'totalDays'         => 0,
                'isCompleted'       => false,
                'completedCount'    => 0,
                'progressPercent'   => 0,
                'passages'          => [],
                'passageCount'      => 0,
                'completedPassages' => [],
                'partnerProgress'   => null,
                'durationType'      => 'annual',
                'todayNote'         => '',
                'greetingContext'   => $greetingContext,
                'weeklyProgress'    => 0,
                'nextMilestone'     => null,
                'availablePlans'    => $availablePlans,
            ];
        }

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

        $isCompleted       = false;
        $completedCount    = 0;
        $passages          = [];
        $completedPassages = [];
        $passageCount      = 1;

        if ($todayReading) {
            $passages     = CwmprogressHelper::splitPassages($todayReading->reading);
            $passageCount = \count($passages);
        }

        $isCompleted       = CwmprogressHelper::isCompleted($db, $userId, $planId, $currentDay, $passageCount);
        $completedCount    = CwmprogressHelper::getCompletedCount($db, $userId, $planId);
        $completedPassages = CwmprogressHelper::getCompletedPassages($db, $userId, $planId, $currentDay);

        $progressPercent = ($totalDays > 0) ? round(($completedCount / $totalDays) * 100) : 0;

        $partnerProgress = CwmpartnerHelper::getPartnerProgress($db, $userId);
        $todayNote       = CwmnotesHelper::getNote($db, $userId, $planId, $currentDay) ?? '';
        $weeklyProgress  = self::getWeeklyProgress($db, $userId, $planId);

        if ($completedCount === 0) {
            $greetingContext = 'new_user';
        } elseif ($isCompleted) {
            $greetingContext = 'completed_today';
        } else {
            $greetingContext = 'returning';
        }

        $nextMilestone = self::calculateMilestone(
            $completedCount,
            $totalDays,
            (int) ($userData->streak_current ?? 0),
            (int) ($userData->streak_best ?? 0)
        );

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
            'greetingContext'   => $greetingContext,
            'weeklyProgress'    => $weeklyProgress,
            'nextMilestone'     => $nextMilestone,
            'availablePlans'    => [],
        ];
    }

    /**
     * Fetch published plans for the onboarding picker.
     *
     * @return  object[]  Plan rows ordered by the ordering column.
     *
     * @since   5.5.0
     */
    private static function getAvailablePlans(DatabaseInterface $db): array
    {
        $query = $db->getQuery(true)
            ->select(
                [
                    $db->quoteName('id'),
                    $db->quoteName('alias'),
                    $db->quoteName('title'),
                    $db->quoteName('description'),
                    $db->quoteName('testament'),
                    $db->quoteName('duration_type'),
                    $db->quoteName('total_days'),
                ]
            )
            ->from($db->quoteName('#__livingword_plans'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC');
        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Count readings completed in the last 7 days.
     *
     * @param   DatabaseInterface  $db      Database
     * @param   int                $userId  User ID
     * @param   int                $planId  Plan ID
     *
     * @return  int
     *
     * @since   5.4.0
     */
    private static function getWeeklyProgress(DatabaseInterface $db, int $userId, int $planId): int
    {
        $weekAgo = (new \DateTime('-7 days'))->format('Y-m-d H:i:s');

        $query = $db->getQuery(true)
            ->select('COUNT(DISTINCT ' . $db->quoteName('day') . ')')
            ->from($db->quoteName('#__livingword_progress'))
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('plan_id') . ' = ' . $planId)
            ->where($db->quoteName('completed_at') . ' >= ' . $db->quote($weekAgo));

        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    /**
     * Calculate the next motivational milestone.
     *
     * @param   int  $completed      Completed readings
     * @param   int  $total          Total readings
     * @param   int  $streakCurrent  Current streak
     * @param   int  $streakBest     Best streak
     *
     * @return  ?object  {type, message_key, values} or null
     *
     * @since   5.4.0
     */
    private static function calculateMilestone(int $completed, int $total, int $streakCurrent, int $streakBest): ?object
    {
        // Streak milestone: close to matching or beating best
        if ($streakBest > 0 && $streakCurrent > 0 && $streakCurrent < $streakBest) {
            $gap = $streakBest - $streakCurrent;

            if ($gap <= 7) {
                return (object) [
                    'type'   => 'streak',
                    'key'    => 'COM_LIVINGWORD_MILESTONE_STREAK',
                    'values' => [$gap, $streakBest],
                ];
            }
        }

        // Progress milestone: next 10% boundary
        if ($total > 0) {
            $currentPercent = ($completed / $total) * 100;
            $nextBoundary   = (int) (ceil(($currentPercent + 0.1) / 10) * 10);

            if ($nextBoundary <= 100) {
                $needed = (int) ceil(($nextBoundary / 100) * $total) - $completed;

                if ($needed > 0 && $needed <= 30) {
                    return (object) [
                        'type'   => 'progress',
                        'key'    => 'COM_LIVINGWORD_MILESTONE_PROGRESS',
                        'values' => [$needed, $nextBoundary],
                    ];
                }
            }
        }

        return null;
    }
}
