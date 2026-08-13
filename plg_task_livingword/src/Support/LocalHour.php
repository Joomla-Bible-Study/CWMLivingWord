<?php

/**
 * @package    Livingword.Plugin
 * @subpackage Task.Livingword
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Plugin\Task\Livingword\Support;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * What hour it is where a subscriber lives.
 *
 * Deliberately free of Joomla: the daily-email routine decides who to mail by
 * comparing a stored preferred hour against the clock, and that rule was wrong
 * for the whole life of the feature — it compared against date('G'), which
 * Joomla pins to UTC, so a reader in America/Chicago who asked for 6am was
 * mailed at 1am and the timezone stored on the same row was read by nothing.
 *
 * It stayed wrong because it could not be tested: the rule lived inside a
 * plugin method that needs a database, a task event and a mailer to reach. A
 * class with no dependencies can be exercised in a unit test at any hour of any
 * day, including the ones where daylight saving changes the answer.
 *
 * @since  __DEPLOY_VERSION__
 */
final class LocalHour
{
    /**
     * The hour of the day, 0-23, in the first timezone that makes sense.
     *
     * Falls back rather than rejects. A stored zone can outlive a tzdata
     * update, and a reader whose zone was renamed should still get their
     * email — at the site's hour, which is what an administrator means by
     * "6am" for somebody who never chose.
     *
     * @param   string              $timezone  Subscriber timezone, possibly empty
     * @param   string              $fallback  Site timezone to use instead
     * @param   \DateTimeImmutable  $now       The moment being asked about
     *
     * @return  int  Hour of the day, 0-23
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function inZone(string $timezone, string $fallback, \DateTimeImmutable $now): int
    {
        foreach ([$timezone, $fallback, 'UTC'] as $candidate) {
            if (trim($candidate) === '') {
                continue;
            }

            try {
                return (int) $now->setTimezone(new \DateTimeZone(trim($candidate)))->format('G');
            } catch (\Exception) {
                continue;
            }
        }

        return (int) $now->format('G');
    }
}
