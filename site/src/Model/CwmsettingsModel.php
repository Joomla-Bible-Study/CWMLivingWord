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

use CWM\Component\Livingword\Site\Helper\CwmpartnerHelper;
use CWM\Component\Livingword\Site\Helper\CwmreadingHelper;
use CWM\Component\Livingword\Site\Helper\CwmuserHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * User settings model for site frontend.
 *
 * @since  5.0.0
 */
class CwmsettingsModel extends BaseDatabaseModel
{
    /**
     * Get user settings.
     *
     * @return  object
     *
     * @throws \Exception
     * @since   5.0.0
     */
    public function getUserSettings(): object
    {
        $db     = $this->getDatabase();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        return CwmuserHelper::getUserData($db, $userId);
    }

    /**
     * Get available accountability partners for dropdown.
     *
     * @return  array  Array of user objects with id and name
     *
     * @since   5.6.0
     */
    public function getAvailablePartners(): array
    {
        $db     = $this->getDatabase();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        return CwmpartnerHelper::getAvailablePartners($db, $userId);
    }

    /**
     * Get available plans for the settings form.
     *
     * @return  array  Array of plan objects
     *
     * @since   5.0.0
     */
    public function getAvailablePlans(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'alias', 'title', 'description', 'audio', 'testament']))
            ->from($db->quoteName('#__livingword_plans'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Save user settings from form submission.
     *
     * @param   array  $data  Form data
     *
     * @return  bool
     *
     * @throws \Exception
     * @since   5.0.0
     */
    public function saveSettings(array $data): bool
    {
        $db     = $this->getDatabase();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        if ($userId === 0) {
            return false;
        }

        $planId    = (int) ($data['plan_id'] ?? 0);
        $startDate = $data['start_date'] ?? date('Y-m-d');
        $action    = $data['action'] ?? '';

        // Calculate date_offset based on action
        $dateOffset = (int) ($data['date_offset'] ?? 0);

        if ($action === 'skip_to_today') {
            // Reset offset so current day matches today's actual date position
            $dateOffset = 0;
        } elseif ($action === 'restart') {
            // Set start date to today, reset offset
            $startDate  = date('Y-m-d');
            $dateOffset = 0;
        } elseif (!empty($data['date_offset_day'])) {
            // Jump to specific day — calculate the offset needed
            $targetDay = (int) $data['date_offset_day'];
            $totalDays = CwmreadingHelper::getPlanTotalDays($db, $planId);

            if ($totalDays > 0 && $targetDay >= 1 && $targetDay <= $totalDays) {
                $naturalDay = CwmreadingHelper::getCurrentReadingDay($startDate, 0, $totalDays);
                $dateOffset = $targetDay - $naturalDay;
            }
        }

        $partnerId = !empty($data['accountability_partner_id'])
            ? (int) $data['accountability_partner_id']
            : null;

        $settings = (object) [
            'user_id'                   => $userId,
            'plan_id'                   => $planId,
            'bible_version'             => $data['bible_version'] ?? 'kjv',
            'email'                     => (int) ($data['email'] ?? 0),
            'plan_view'                 => (int) ($data['plan_view'] ?? 0),
            'start_date'                => $startDate,
            'date_offset'               => $dateOffset,
            'accountability_partner_id' => $partnerId,
            'share_progress'            => (int) ($data['share_progress'] ?? 0),
        ];

        return CwmuserHelper::saveUserData($db, $settings);
    }
}
