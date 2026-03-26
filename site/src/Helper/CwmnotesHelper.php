<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\Database\DatabaseInterface;

/**
 * Helper for reading notes/journal entries.
 *
 * One note per user per plan per day, stored in #__livingword_notes.
 *
 * @since  5.2.0
 */
class CwmnotesHelper
{
    /**
     * Get a single note for a specific day.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  User ID
     * @param   int                $planId  Plan ID
     * @param   int                $day     Day number (1-based)
     *
     * @return  ?string  Note text or null if none exists
     *
     * @since   5.2.0
     */
    public static function getNote(DatabaseInterface $db, int $userId, int $planId, int $day): ?string
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('note_text'))
            ->from($db->quoteName('#__livingword_notes'))
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('plan_id') . ' = ' . $planId)
            ->where($db->quoteName('day') . ' = ' . $day);

        $db->setQuery($query);

        $result = $db->loadResult();

        return $result !== null ? (string) $result : null;
    }

    /**
     * Save (upsert) a note. Deletes the row if text is empty.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  User ID
     * @param   int                $planId  Plan ID
     * @param   int                $day     Day number (1-based)
     * @param   string             $text    Note text
     *
     * @return  bool  True on success
     *
     * @since   5.2.0
     */
    public static function saveNote(DatabaseInterface $db, int $userId, int $planId, int $day, string $text): bool
    {
        $text = trim($text);

        if ($text === '') {
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__livingword_notes'))
                ->where($db->quoteName('user_id') . ' = ' . $userId)
                ->where($db->quoteName('plan_id') . ' = ' . $planId)
                ->where($db->quoteName('day') . ' = ' . $day);

            $db->setQuery($query);
            $db->execute();

            return true;
        }

        $now = (new \DateTime())->format('Y-m-d H:i:s');

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_notes'))
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('plan_id') . ' = ' . $planId)
            ->where($db->quoteName('day') . ' = ' . $day);

        $db->setQuery($query);
        $exists = (int) $db->loadResult() > 0;

        if ($exists) {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__livingword_notes'))
                ->set($db->quoteName('note_text') . ' = ' . $db->quote($text))
                ->set($db->quoteName('modified') . ' = ' . $db->quote($now))
                ->where($db->quoteName('user_id') . ' = ' . $userId)
                ->where($db->quoteName('plan_id') . ' = ' . $planId)
                ->where($db->quoteName('day') . ' = ' . $day);
        } else {
            $query = $db->getQuery(true)
                ->insert($db->quoteName('#__livingword_notes'))
                ->columns([
                    $db->quoteName('user_id'),
                    $db->quoteName('plan_id'),
                    $db->quoteName('day'),
                    $db->quoteName('note_text'),
                    $db->quoteName('created'),
                    $db->quoteName('modified'),
                ])
                ->values(implode(',', [
                    $userId,
                    $planId,
                    $day,
                    $db->quote($text),
                    $db->quote($now),
                    $db->quote($now),
                ]));
        }

        $db->setQuery($query);
        $db->execute();

        return true;
    }

    /**
     * Get all notes for a user's plan as an associative array [day => note_text].
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  User ID
     * @param   int                $planId  Plan ID
     *
     * @return  array<int, string>  Associative array keyed by day number
     *
     * @since   5.2.0
     */
    public static function getNotesForPlan(DatabaseInterface $db, int $userId, int $planId): array
    {
        $query = $db->getQuery(true)
            ->select([$db->quoteName('day'), $db->quoteName('note_text')])
            ->from($db->quoteName('#__livingword_notes'))
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('plan_id') . ' = ' . $planId)
            ->order($db->quoteName('day') . ' ASC');

        $db->setQuery($query);

        $rows  = $db->loadObjectList();
        $notes = [];

        foreach ($rows as $row) {
            $notes[(int) $row->day] = $row->note_text;
        }

        return $notes;
    }

    /**
     * Get days that have notes (for indicator display).
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  User ID
     * @param   int                $planId  Plan ID
     *
     * @return  array<int>  Array of day numbers that have notes
     *
     * @since   5.2.0
     */
    public static function getNoteDays(DatabaseInterface $db, int $userId, int $planId): array
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('day'))
            ->from($db->quoteName('#__livingword_notes'))
            ->where($db->quoteName('user_id') . ' = ' . $userId)
            ->where($db->quoteName('plan_id') . ' = ' . $planId);

        $db->setQuery($query);

        return array_map('intval', $db->loadColumn());
    }
}
