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
 * Accountability partner helper.
 *
 * Manages pairing between two users and exposes partner progress data.
 * Partnership is opt-in: both users must set each other as partners
 * AND enable share_progress for mutual visibility.
 *
 * @since  5.6.0
 */
class CwmpartnerHelper
{
    /**
     * Get partner information for a user, including the partner's progress.
     *
     * Returns null if no partner is set or partner doesn't share progress.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  The current user's Joomla ID
     *
     * @return  ?object  Partner data with progress, or null
     *
     * @since   5.6.0
     */
    public static function getPartnerProgress(DatabaseInterface $db, int $userId): ?object
    {
        if ($userId === 0) {
            return null;
        }

        // Get current user's partner setting
        $query = $db->getQuery(true)
            ->select($db->quoteName(['accountability_partner_id', 'share_progress']))
            ->from($db->quoteName('#__livingword_users'))
            ->where($db->quoteName('user_id') . ' = ' . $userId);

        $db->setQuery($query);
        $myRow = $db->loadObject();

        if (!$myRow || empty($myRow->accountability_partner_id)) {
            return null;
        }

        $partnerId = (int) $myRow->accountability_partner_id;

        // Get partner's LivingWord data + Joomla user name
        $query = $db->getQuery(true)
            ->select('lw.*, u.name AS partner_name')
            ->from($db->quoteName('#__livingword_users', 'lw'))
            ->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('lw.user_id'))
            ->where($db->quoteName('lw.user_id') . ' = ' . $partnerId);

        $db->setQuery($query);
        $partner = $db->loadObject();

        if (!$partner) {
            return null;
        }

        // Partner must also share progress for us to see it
        if (!(int) $partner->share_progress) {
            return (object) [
                'partner_name'    => $partner->partner_name,
                'partner_id'      => $partnerId,
                'shares_progress' => false,
                'is_mutual'       => self::isMutualPartnership($db, $userId, $partnerId),
            ];
        }

        // Check if this is a mutual partnership
        $isMutual = self::isMutualPartnership($db, $userId, $partnerId);

        // Get partner's progress stats
        $planId     = (int) $partner->plan_id;
        $totalDays  = CwmreadingHelper::getPlanTotalDays($db, $planId);
        $currentDay = CwmreadingHelper::getCurrentReadingDay(
            $partner->start_date ?? '',
            (int) $partner->date_offset,
            $totalDays ?: 365
        );
        $completedCount  = CwmprogressHelper::getCompletedCount($db, $partnerId, $planId);
        $progressPercent = ($totalDays > 0) ? round(($completedCount / $totalDays) * 100) : 0;

        // Get plan name
        $planInfo = CwmreadingHelper::getPlanById($db, $planId);

        return (object) [
            'partner_name'     => $partner->partner_name,
            'partner_id'       => $partnerId,
            'shares_progress'  => true,
            'is_mutual'        => $isMutual,
            'plan_name'        => ($planInfo !== null) ? ($planInfo->description ?? '') : '',
            'current_day'      => $currentDay,
            'total_days'       => $totalDays,
            'completed_count'  => $completedCount,
            'progress_percent' => $progressPercent,
            'streak_current'   => (int) ($partner->streak_current ?? 0),
            'streak_best'      => (int) ($partner->streak_best ?? 0),
        ];
    }

    /**
     * Check if two users have a mutual partnership (both point to each other).
     *
     * @param   DatabaseInterface  $db       Database instance
     * @param   int                $userId   First user ID
     * @param   int                $partnerId  Second user ID
     *
     * @return  bool
     *
     * @since   5.6.0
     */
    public static function isMutualPartnership(DatabaseInterface $db, int $userId, int $partnerId): bool
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('accountability_partner_id'))
            ->from($db->quoteName('#__livingword_users'))
            ->where($db->quoteName('user_id') . ' = ' . $partnerId);

        $db->setQuery($query);
        $theirPartnerId = (int) $db->loadResult();

        return $theirPartnerId === $userId;
    }

    /**
     * Get a list of LivingWord users for the partner selection dropdown.
     *
     * Excludes the current user. Only returns users who have a LivingWord record.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  Current user ID to exclude
     *
     * @return  array  Array of objects with id and name
     *
     * @since   5.6.0
     */
    public static function getAvailablePartners(DatabaseInterface $db, int $userId): array
    {
        $query = $db->getQuery(true)
            ->select('u.id, u.name')
            ->from($db->quoteName('#__livingword_users', 'lw'))
            ->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('lw.user_id'))
            ->where($db->quoteName('lw.user_id') . ' != ' . $userId)
            ->where($db->quoteName('u.block') . ' = 0')
            ->order($db->quoteName('u.name') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Get partner progress summary data for email digest.
     *
     * Returns progress for all users who have a mutual partner and share progress.
     *
     * @param   DatabaseInterface  $db  Database instance
     *
     * @return  array  Array of objects with user + partner progress data
     *
     * @since   5.6.0
     */
    public static function getPartnerPairsForEmail(DatabaseInterface $db): array
    {
        // Get all users who have a partner set and share progress
        $query = $db->getQuery(true)
            ->select('lw.user_id, lw.accountability_partner_id, lw.plan_id, lw.start_date, lw.date_offset')
            ->select('lw.streak_current, lw.streak_best, lw.share_progress')
            ->select('u.name, u.email AS user_email')
            ->from($db->quoteName('#__livingword_users', 'lw'))
            ->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('lw.user_id'))
            ->where($db->quoteName('lw.accountability_partner_id') . ' IS NOT NULL')
            ->where($db->quoteName('lw.email') . ' = 1')
            ->where($db->quoteName('u.block') . ' = 0');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }
}
