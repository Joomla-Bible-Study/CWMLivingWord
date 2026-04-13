<?php

/**
 * @package    Livingword.Module
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Module\Livingword\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmreadingHelper;
use CWM\Component\Livingword\Site\Helper\CwmuserHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Registry\Registry;

/**
 * Module helper for mod_livingword
 *
 * @since  5.0.0
 */
class LivingwordHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * Get today's reading data for the module display.
     *
     * Returns an empty-state object when the user is a guest or has no plan
     * subscription, so the template can show an appropriate message instead
     * of crashing.
     *
     * @param   Registry  $params  Module parameters
     *
     * @return  object  Object with readingText, bible_version, planDescription, currentDay, totalDays, hasSubscription
     *
     * @since   5.0.0
     */
    public function getTodayReading(Registry $params): object
    {
        $db     = $this->getDatabase();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        $empty = (object) [
            'readingText'     => '',
            'bible_version'   => 'kjv',
            'planDescription' => '',
            'currentDay'      => 0,
            'totalDays'       => 0,
            'hasSubscription' => false,
        ];

        // Guest user or no subscription — return empty state
        $userData = CwmuserHelper::getUserData($db, $userId);
        $planId   = (int) ($userData->plan_id ?? 0);

        if ($planId === 0) {
            return $empty;
        }

        try {
            $plan      = CwmreadingHelper::getPlanById($db, $planId);
            $totalDays = CwmreadingHelper::getPlanTotalDays($db, $planId);

            if ($totalDays === 0) {
                return $empty;
            }

            $currentDay = CwmreadingHelper::getReadingDayForPlan(
                $plan,
                $userData->start_date ?? '',
                (int) ($userData->date_offset ?? 0),
                $totalDays,
                $db,
                $userId
            );

            $reading = CwmreadingHelper::getReadingForDay($db, $planId, $currentDay);

            return (object) [
                'readingText'     => $reading->reading ?? '',
                'bible_version'   => $userData->bible_version ?? 'kjv',
                'planDescription' => $plan->description ?? '',
                'currentDay'      => $currentDay,
                'totalDays'       => $totalDays,
                'hasSubscription' => true,
            ];
        } catch (\Exception) {
            return $empty;
        }
    }
}
