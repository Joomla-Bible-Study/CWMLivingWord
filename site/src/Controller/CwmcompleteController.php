<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmprogressHelper;
use CWM\Component\Livingword\Site\Helper\CwmreadingHelper;
use CWM\Component\Livingword\Site\Helper\CwmuserHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

/**
 * Public reading completion controller — no login required.
 *
 * Handles one-click reading completion via action token from daily emails.
 *
 * @since  5.3.0
 */
class CwmcompleteController extends BaseController
{
    /**
     * Mark today's reading as complete using an action token.
     *
     * URL: index.php?option=com_livingword&task=cwmcomplete.complete&token=XXX
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public function complete(): void
    {
        $token = $this->input->getString('token', '');
        $db    = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $app   = $this->app;

        $userData = CwmuserHelper::getUserByActionToken($db, $token);

        if (!$userData) {
            $app->setUserState('com_livingword.complete.success', false);
            $this->setRedirect(Route::_('index.php?option=com_livingword&view=cwmcomplete', false));

            return;
        }

        $userId = (int) $userData->user_id;
        $planId = (int) $userData->plan_id;

        $planInfo  = CwmreadingHelper::getPlanById($db, $planId);
        $totalDays = CwmreadingHelper::getPlanTotalDays($db, $planId);

        $currentDay = CwmreadingHelper::getReadingDayForPlan(
            $planInfo,
            $userData->start_date ?? '',
            (int) $userData->date_offset,
            $totalDays ?: 365,
            $db,
            $userId
        );

        $reading = CwmreadingHelper::getReadingForDay($db, $planId, $currentDay);

        if (!$reading) {
            $app->setUserState('com_livingword.complete.success', false);
            $this->setRedirect(Route::_('index.php?option=com_livingword&view=cwmcomplete', false));

            return;
        }

        $passageCount = CwmprogressHelper::countPassages($reading->reading);

        // Mark all passages as complete (idempotent)
        for ($i = 0; $i < $passageCount; $i++) {
            if (!CwmprogressHelper::isPassageCompleted($db, $userId, $planId, $currentDay, $i)) {
                CwmprogressHelper::markPassageComplete($db, $userId, $planId, $currentDay, $i);
            }
        }

        // Update streak
        CwmprogressHelper::updateStreak($db, $userId);

        $app->setUserState('com_livingword.complete.success', true);
        $app->setUserState('com_livingword.complete.day', $currentDay);
        $app->setUserState('com_livingword.complete.reading', $reading->reading);

        $this->setRedirect(Route::_('index.php?option=com_livingword&view=cwmcomplete', false));
    }
}
