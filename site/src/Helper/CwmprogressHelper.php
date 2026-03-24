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
 * Reading progress tracking helper.
 *
 * Manages per-day completion records in #__livingword_progress.
 *
 * @since  5.3.0
 */
class CwmprogressHelper
{
    /**
     * Check if a specific day is completed for a user/plan.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  Joomla user ID
     * @param   int                $planId  Plan ID
     * @param   int                $day     Day number (1-based)
     *
     * @return  bool
     *
     * @since   5.3.0
     */
    public static function isCompleted(DatabaseInterface $db, int $userId, int $planId, int $day): bool
    {
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_progress'))
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('plan_id') . ' = ' . $planId)
            ->where($db->quoteName('day') . ' = ' . $day);

        $db->setQuery($query);

        return (int) $db->loadResult() > 0;
    }

    /**
     * Mark a day as complete.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  Joomla user ID
     * @param   int                $planId  Plan ID
     * @param   int                $day     Day number (1-based)
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public static function markComplete(DatabaseInterface $db, int $userId, int $planId, int $day): void
    {
        $record = (object) [
            'user_id' => $userId,
            'plan_id' => $planId,
            'day'     => $day,
        ];

        try {
            $db->insertObject('#__livingword_progress', $record);
        } catch (\RuntimeException) {
            // Already exists (unique constraint) — ignore
        }
    }

    /**
     * Mark a day as incomplete (remove completion record).
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  Joomla user ID
     * @param   int                $planId  Plan ID
     * @param   int                $day     Day number (1-based)
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public static function markIncomplete(DatabaseInterface $db, int $userId, int $planId, int $day): void
    {
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__livingword_progress'))
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('plan_id') . ' = ' . $planId)
            ->where($db->quoteName('day') . ' = ' . $day);

        $db->setQuery($query);
        $db->execute();
    }

    /**
     * Toggle completion for a day. Returns the new state.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  Joomla user ID
     * @param   int                $planId  Plan ID
     * @param   int                $day     Day number (1-based)
     *
     * @return  bool  True if now completed, false if now incomplete
     *
     * @since   5.3.0
     */
    public static function toggleComplete(DatabaseInterface $db, int $userId, int $planId, int $day): bool
    {
        if (self::isCompleted($db, $userId, $planId, $day)) {
            self::markIncomplete($db, $userId, $planId, $day);

            return false;
        }

        self::markComplete($db, $userId, $planId, $day);
        self::updateStreak($db, $userId);

        return true;
    }

    /**
     * Update the user's reading streak based on today's date.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  Joomla user ID
     *
     * @return  void
     *
     * @since   5.4.0
     */
    public static function updateStreak(DatabaseInterface $db, int $userId): void
    {
        $today = date('Y-m-d');

        $query = $db->getQuery(true)
            ->select($db->quoteName(['streak_current', 'streak_best', 'streak_last_date']))
            ->from($db->quoteName('#__livingword_users'))
            ->where($db->quoteName('user_id') . ' = ' . $userId);

        $db->setQuery($query);
        $row = $db->loadObject();

        if (!$row) {
            return;
        }

        $lastDate = $row->streak_last_date;
        $current  = (int) $row->streak_current;
        $best     = (int) $row->streak_best;

        if ($lastDate === $today) {
            // Already counted today
            return;
        }

        $yesterday = date('Y-m-d', strtotime('-1 day'));

        if ($lastDate === $yesterday) {
            // Consecutive day — extend streak
            $current++;
        } else {
            // Streak broken — start fresh
            $current = 1;
        }

        if ($current > $best) {
            $best = $current;
        }

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__livingword_users'))
            ->set($db->quoteName('streak_current') . ' = ' . $current)
            ->set($db->quoteName('streak_best') . ' = ' . $best)
            ->set($db->quoteName('streak_last_date') . ' = ' . $db->quote($today))
            ->where($db->quoteName('user_id') . ' = ' . $userId);

        $db->setQuery($query);
        $db->execute();
    }

    /**
     * Get all completed day numbers for a user/plan.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  Joomla user ID
     * @param   int                $planId  Plan ID
     *
     * @return  array  Array of completed day numbers
     *
     * @since   5.3.0
     */
    public static function getCompletedDays(DatabaseInterface $db, int $userId, int $planId): array
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('day'))
            ->from($db->quoteName('#__livingword_progress'))
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('plan_id') . ' = ' . $planId)
            ->order($db->quoteName('day') . ' ASC');

        $db->setQuery($query);

        return $db->loadColumn() ?: [];
    }

    /**
     * Get the count of completed days for a user/plan.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  Joomla user ID
     * @param   int                $planId  Plan ID
     *
     * @return  int
     *
     * @since   5.3.0
     */
    public static function getCompletedCount(DatabaseInterface $db, int $userId, int $planId): int
    {
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_progress'))
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('plan_id') . ' = ' . $planId);

        $db->setQuery($query);

        return (int) $db->loadResult();
    }
}
