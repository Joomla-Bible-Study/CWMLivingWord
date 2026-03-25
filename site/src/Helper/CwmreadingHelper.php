<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\Database\DatabaseInterface;

/**
 * Reading plan calculation helper.
 *
 * Handles date calculations, current reading day determination,
 * and plan data retrieval. All queries use plan_id (integer FK).
 *
 * Supports three duration types:
 * - annual: repeats yearly (wraps around via modulo)
 * - fixed: runs once from start_date, capped at total_days
 * - self_paced: advances only when user marks readings complete
 *
 * @since  5.0.0
 */
class CwmreadingHelper
{
    /**
     * Calculate the current reading day based on the plan start date and offset.
     *
     * For annual plans, wraps around using modulo.
     * For fixed plans, caps at totalDays (no wrapping).
     *
     * @param   string  $startDate  The plan start date (Y-m-d)
     * @param   int     $offset     Date offset in days
     * @param   int     $totalDays  Total days in the plan
     *
     * @return  int  Current reading day (1-based)
     *
     * @since   5.0.0
     */
    public static function getCurrentReadingDay(string $startDate, int $offset = 0, int $totalDays = 365): int
    {
        if (empty($startDate) || $startDate === '0000-00-00') {
            $startDate = date('Y-01-01');
        }

        $start   = new \DateTime($startDate);
        $now     = new \DateTime('today');
        $diff    = (int) $now->diff($start)->days;
        $reading = ($diff + $offset) % $totalDays;

        if ($reading < 0) {
            $reading += $totalDays;
        }

        return $reading + 1;
    }

    /**
     * Calculate the current reading day for a fixed-duration plan.
     *
     * Returns the day number capped at totalDays (no wrapping).
     * Returns 0 if the plan hasn't started yet, or totalDays if finished.
     *
     * @param   string  $startDate  The plan start date (Y-m-d)
     * @param   int     $offset     Date offset in days
     * @param   int     $totalDays  Total days in the plan
     *
     * @return  int  Current reading day (1-based), 0 if not started
     *
     * @since   5.7.0
     */
    public static function getFixedReadingDay(string $startDate, int $offset = 0, int $totalDays = 365): int
    {
        if (empty($startDate) || $startDate === '0000-00-00') {
            return 1;
        }

        $start = new \DateTime($startDate);
        $now   = new \DateTime('today');
        $diff  = (int) $now->diff($start)->days;

        if ($now < $start) {
            return 0;
        }

        $day = $diff + $offset + 1;

        return min($day, $totalDays);
    }

    /**
     * Get the current day for a self-paced plan.
     *
     * Returns the next uncompleted day number (first day without a progress record).
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  Joomla user ID
     * @param   int                $planId  Plan ID
     * @param   int                $totalDays  Total days in the plan
     *
     * @return  int  Next reading day (1-based), or totalDays if all complete
     *
     * @since   5.7.0
     */
    public static function getSelfPacedDay(DatabaseInterface $db, int $userId, int $planId, int $totalDays): int
    {
        $completedDays = CwmprogressHelper::getCompletedDays($db, $userId, $planId);

        if (empty($completedDays)) {
            return 1;
        }

        $completedSet = array_flip($completedDays);

        for ($day = 1; $day <= $totalDays; $day++) {
            if (!isset($completedSet[$day])) {
                return $day;
            }
        }

        return $totalDays;
    }

    /**
     * Get the current reading day based on plan duration type.
     *
     * @param   ?object            $plan      Plan object (needs duration_type, total_days)
     * @param   string             $startDate User's start date
     * @param   int                $offset    Date offset
     * @param   int                $totalDays Total readings in the plan
     * @param   DatabaseInterface  $db        Database (needed for self_paced)
     * @param   int                $userId    User ID (needed for self_paced)
     *
     * @return  int  Current reading day (1-based)
     *
     * @since   5.7.0
     */
    public static function getReadingDayForPlan(
        ?object $plan,
        string $startDate,
        int $offset,
        int $totalDays,
        DatabaseInterface $db,
        int $userId
    ): int {
        $durationType = $plan->duration_type ?? 'annual';
        $planId       = (int) ($plan->id ?? 0);

        return match ($durationType) {
            'fixed'      => self::getFixedReadingDay($startDate, $offset, $totalDays),
            'self_paced' => self::getSelfPacedDay($db, $userId, $planId, $totalDays),
            default      => self::getCurrentReadingDay($startDate, $offset, $totalDays),
        };
    }

    /**
     * Check if a fixed-duration plan is complete (past the last day).
     *
     * @param   string  $startDate  The plan start date
     * @param   int     $offset     Date offset
     * @param   int     $totalDays  Total days in the plan
     *
     * @return  bool
     *
     * @since   5.7.0
     */
    public static function isFixedPlanComplete(string $startDate, int $offset, int $totalDays): bool
    {
        $day = self::getFixedReadingDay($startDate, $offset, $totalDays);

        return $day >= $totalDays;
    }

    /**
     * Calculate the difference in days between two dates.
     *
     * @param   string  $end    End date
     * @param   string  $begin  Begin date
     *
     * @return  int  Number of days
     *
     * @since   5.0.0
     */
    public static function dateDiff(string $end, string $begin): int
    {
        $endDate   = new \DateTime($end);
        $beginDate = new \DateTime($begin);

        return (int) $endDate->diff($beginDate)->days;
    }

    /**
     * Get the total number of readings in a plan.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $planId  Plan ID
     *
     * @return  int  Total readings count
     *
     * @since   5.0.0
     */
    public static function getPlanTotalDays(DatabaseInterface $db, int $planId): int
    {
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_plans_details'))
            ->where($db->quoteName('plan_id') . ' = ' . $planId);

        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    /**
     * Get the reading data for a specific day in a plan.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $planId  Plan ID
     * @param   int                $day     Day number (1-based, maps to ordering)
     *
     * @return  ?object  Reading record or null
     *
     * @since   5.0.0
     */
    public static function getReadingForDay(DatabaseInterface $db, int $planId, int $day): ?object
    {
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__livingword_plans_details'))
            ->where($db->quoteName('plan_id') . ' = ' . $planId)
            ->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query, $day - 1, 1);

        return $db->loadObject() ?: null;
    }

    /**
     * Get all readings for a plan, ordered by day.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $planId  Plan ID
     *
     * @return  array  Array of reading objects
     *
     * @since   5.0.0
     */
    public static function getAllReadings(DatabaseInterface $db, int $planId): array
    {
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__livingword_plans_details'))
            ->where($db->quoteName('plan_id') . ' = ' . $planId)
            ->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Get plan metadata by plan ID.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $planId  Plan ID
     *
     * @return  ?object  Plan record or null
     *
     * @since   5.2.0
     */
    public static function getPlanById(DatabaseInterface $db, int $planId): ?object
    {
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__livingword_plans'))
            ->where($db->quoteName('id') . ' = ' . $planId)
            ->where($db->quoteName('published') . ' = 1');

        $db->setQuery($query);

        return $db->loadObject() ?: null;
    }
}
