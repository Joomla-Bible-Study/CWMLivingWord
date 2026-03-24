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
 * @since  5.0.0
 */
class CwmreadingHelper
{
    /**
     * Calculate the current reading day based on the plan start date and offset.
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
