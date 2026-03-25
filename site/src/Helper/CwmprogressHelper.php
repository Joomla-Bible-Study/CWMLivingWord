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
 * Manages per-day and per-passage completion records in #__livingword_progress.
 *
 * @since  5.3.0
 */
class CwmprogressHelper
{
    /**
     * Check if a specific day is fully completed for a user/plan.
     *
     * A day is complete when all passages within it are marked complete.
     * For single-passage days (passageCount=1), this checks passage_index=0.
     *
     * @param   DatabaseInterface  $db            Database instance
     * @param   int                $userId        Joomla user ID
     * @param   int                $planId        Plan ID
     * @param   int                $day           Day number (1-based)
     * @param   int                $passageCount  Total passages in this day's reading
     *
     * @return  bool
     *
     * @since   5.3.0
     */
    public static function isCompleted(
        DatabaseInterface $db,
        int $userId,
        int $planId,
        int $day,
        int $passageCount = 1
    ): bool {
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_progress'))
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('plan_id') . ' = ' . $planId)
            ->where($db->quoteName('day') . ' = ' . $day);

        $db->setQuery($query);
        $count = (int) $db->loadResult();

        return $count >= max($passageCount, 1);
    }

    /**
     * Check if a specific passage within a day is completed.
     *
     * @param   DatabaseInterface  $db            Database instance
     * @param   int                $userId        Joomla user ID
     * @param   int                $planId        Plan ID
     * @param   int                $day           Day number (1-based)
     * @param   int                $passageIndex  0-based passage index
     *
     * @return  bool
     *
     * @since   5.5.0
     */
    public static function isPassageCompleted(
        DatabaseInterface $db,
        int $userId,
        int $planId,
        int $day,
        int $passageIndex
    ): bool {
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_progress'))
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('plan_id') . ' = ' . $planId)
            ->where($db->quoteName('day') . ' = ' . $day)
            ->where($db->quoteName('passage_index') . ' = ' . $passageIndex);

        $db->setQuery($query);

        return (int) $db->loadResult() > 0;
    }

    /**
     * Mark a specific passage as complete.
     *
     * @param   DatabaseInterface  $db            Database instance
     * @param   int                $userId        Joomla user ID
     * @param   int                $planId        Plan ID
     * @param   int                $day           Day number (1-based)
     * @param   int                $passageIndex  0-based passage index
     *
     * @return  void
     *
     * @since   5.5.0
     */
    public static function markPassageComplete(
        DatabaseInterface $db,
        int $userId,
        int $planId,
        int $day,
        int $passageIndex = 0
    ): void {
        $record = (object) [
            'user_id'       => $userId,
            'plan_id'       => $planId,
            'day'           => $day,
            'passage_index' => $passageIndex,
        ];

        try {
            $db->insertObject('#__livingword_progress', $record);
        } catch (\RuntimeException) {
            // Already exists (unique constraint) — ignore
        }
    }

    /**
     * Mark a day as complete (legacy convenience). Marks all passages.
     *
     * @param   DatabaseInterface  $db            Database instance
     * @param   int                $userId        Joomla user ID
     * @param   int                $planId        Plan ID
     * @param   int                $day           Day number (1-based)
     * @param   int                $passageCount  Total passages in day
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public static function markComplete(
        DatabaseInterface $db,
        int $userId,
        int $planId,
        int $day,
        int $passageCount = 1
    ): void {
        for ($i = 0; $i < max($passageCount, 1); $i++) {
            self::markPassageComplete($db, $userId, $planId, $day, $i);
        }
    }

    /**
     * Remove completion for a specific passage.
     *
     * @param   DatabaseInterface  $db            Database instance
     * @param   int                $userId        Joomla user ID
     * @param   int                $planId        Plan ID
     * @param   int                $day           Day number (1-based)
     * @param   int                $passageIndex  0-based passage index
     *
     * @return  void
     *
     * @since   5.5.0
     */
    public static function markPassageIncomplete(
        DatabaseInterface $db,
        int $userId,
        int $planId,
        int $day,
        int $passageIndex
    ): void {
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__livingword_progress'))
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('plan_id') . ' = ' . $planId)
            ->where($db->quoteName('day') . ' = ' . $day)
            ->where($db->quoteName('passage_index') . ' = ' . $passageIndex);

        $db->setQuery($query);
        $db->execute();
    }

    /**
     * Mark a day as incomplete (remove all passage records for the day).
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
     * Toggle completion for a single passage. Returns new state.
     *
     * @param   DatabaseInterface  $db            Database instance
     * @param   int                $userId        Joomla user ID
     * @param   int                $planId        Plan ID
     * @param   int                $day           Day number (1-based)
     * @param   int                $passageIndex  0-based passage index
     * @param   int                $passageCount  Total passages in this day
     *
     * @return  array{passage_completed: bool, day_completed: bool}
     *
     * @since   5.5.0
     */
    public static function togglePassage(
        DatabaseInterface $db,
        int $userId,
        int $planId,
        int $day,
        int $passageIndex,
        int $passageCount
    ): array {
        if (self::isPassageCompleted($db, $userId, $planId, $day, $passageIndex)) {
            self::markPassageIncomplete($db, $userId, $planId, $day, $passageIndex);

            return [
                'passage_completed' => false,
                'day_completed'     => false,
            ];
        }

        self::markPassageComplete($db, $userId, $planId, $day, $passageIndex);
        $dayCompleted = self::isCompleted($db, $userId, $planId, $day, $passageCount);

        if ($dayCompleted) {
            self::updateStreak($db, $userId);
        }

        return [
            'passage_completed' => true,
            'day_completed'     => $dayCompleted,
        ];
    }

    /**
     * Toggle completion for a day (all passages at once). Returns the new state.
     *
     * @param   DatabaseInterface  $db            Database instance
     * @param   int                $userId        Joomla user ID
     * @param   int                $planId        Plan ID
     * @param   int                $day           Day number (1-based)
     * @param   int                $passageCount  Total passages in day
     *
     * @return  bool  True if now completed, false if now incomplete
     *
     * @since   5.3.0
     */
    public static function toggleComplete(
        DatabaseInterface $db,
        int $userId,
        int $planId,
        int $day,
        int $passageCount = 1
    ): bool {
        if (self::isCompleted($db, $userId, $planId, $day, $passageCount)) {
            self::markIncomplete($db, $userId, $planId, $day);

            return false;
        }

        self::markComplete($db, $userId, $planId, $day, $passageCount);
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
     * A day is considered complete if it has at least one passage marked.
     * Use getFullyCompletedDays() for days where all passages are complete.
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
            ->select('DISTINCT ' . $db->quoteName('day'))
            ->from($db->quoteName('#__livingword_progress'))
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('plan_id') . ' = ' . $planId)
            ->order($db->quoteName('day') . ' ASC');

        $db->setQuery($query);

        return $db->loadColumn() ?: [];
    }

    /**
     * Get completed passage indexes for a specific day.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  Joomla user ID
     * @param   int                $planId  Plan ID
     * @param   int                $day     Day number (1-based)
     *
     * @return  array  Array of completed passage indexes (0-based)
     *
     * @since   5.5.0
     */
    public static function getCompletedPassages(DatabaseInterface $db, int $userId, int $planId, int $day): array
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('passage_index'))
            ->from($db->quoteName('#__livingword_progress'))
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('plan_id') . ' = ' . $planId)
            ->where($db->quoteName('day') . ' = ' . $day)
            ->order($db->quoteName('passage_index') . ' ASC');

        $db->setQuery($query);

        return $db->loadColumn() ?: [];
    }

    /**
     * Get passage completion counts per day for a user/plan.
     *
     * Returns an associative array of day => completed_passage_count.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  Joomla user ID
     * @param   int                $planId  Plan ID
     *
     * @return  array<int, int>  day => count of completed passages
     *
     * @since   5.5.0
     */
    public static function getCompletedPassageCounts(DatabaseInterface $db, int $userId, int $planId): array
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('day'))
            ->select('COUNT(*) AS ' . $db->quoteName('passage_count'))
            ->from($db->quoteName('#__livingword_progress'))
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('plan_id') . ' = ' . $planId)
            ->group($db->quoteName('day'))
            ->order($db->quoteName('day') . ' ASC');

        $db->setQuery($query);
        $rows = $db->loadObjectList();

        $result = [];

        foreach ($rows ?: [] as $row) {
            $result[(int) $row->day] = (int) $row->passage_count;
        }

        return $result;
    }

    /**
     * Get the count of fully completed days for a user/plan.
     *
     * For backward compatibility, counts days that have at least one passage marked.
     * This matches the previous behavior where one record = one completed day.
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
            ->select('COUNT(DISTINCT ' . $db->quoteName('day') . ')')
            ->from($db->quoteName('#__livingword_progress'))
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('plan_id') . ' = ' . $planId);

        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    /**
     * Count passages in a reading string (semicolon-separated).
     *
     * @param   string  $reading  The reading reference (e.g. "Genesis 1-3; Psalm 1; Matthew 1")
     *
     * @return  int  Number of passages (minimum 1)
     *
     * @since   5.5.0
     */
    public static function countPassages(string $reading): int
    {
        if (empty(trim($reading))) {
            return 1;
        }

        $passages = array_filter(array_map('trim', explode(';', $reading)));

        return max(\count($passages), 1);
    }

    /**
     * Split a reading string into individual passage references.
     *
     * @param   string  $reading  The reading reference (e.g. "Genesis 1-3; Psalm 1; Matthew 1")
     *
     * @return  array  Array of trimmed passage strings
     *
     * @since   5.5.0
     */
    public static function splitPassages(string $reading): array
    {
        if (empty(trim($reading))) {
            return [$reading];
        }

        $passages = array_filter(array_map('trim', explode(';', $reading)));

        return array_values($passages) ?: [$reading];
    }
}
