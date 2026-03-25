<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmprogressHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

/**
 * AJAX controller for reading completion tracking.
 *
 * Supports both day-level and passage-level toggling.
 *
 * @since  5.3.0
 */
class CwmprogressController extends BaseController
{
    /**
     * Toggle completion for a reading day or individual passage. Returns JSON.
     *
     * Parameters:
     *   - plan_id (int, required)
     *   - day (int, required)
     *   - passage_index (int, optional) — if provided, toggles a single passage
     *   - passage_count (int, optional) — total passages in this day, needed for day-complete check
     *
     * @return  void
     *
     * @since   5.3.0
     */
    public function toggle(): void
    {
        $app = $this->app;
        $app->setHeader('Content-Type', 'application/json; charset=utf-8');

        if (!Session::checkToken('get')) {
            $app->sendHeaders();
            echo new JsonResponse(null, 'Invalid session token', true);
            $app->close();
        }

        $userId = (int) $app->getIdentity()->id;

        if ($userId === 0) {
            $app->sendHeaders();
            echo new JsonResponse(null, 'Login required', true);
            $app->close();
        }

        $planId       = $this->input->getInt('plan_id', 0);
        $day          = $this->input->getInt('day', 0);
        $passageIndex = $this->input->getInt('passage_index', -1);
        $passageCount = $this->input->getInt('passage_count', 1);

        if ($planId <= 0 || $day <= 0) {
            $app->sendHeaders();
            echo new JsonResponse(null, 'Invalid plan or day', true);
            $app->close();
        }

        $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        if ($passageIndex >= 0) {
            // Passage-level toggle
            $result = CwmprogressHelper::togglePassage(
                $db,
                $userId,
                $planId,
                $day,
                $passageIndex,
                max($passageCount, 1)
            );

            $app->sendHeaders();
            echo new JsonResponse([
                'completed'         => $result['day_completed'],
                'passage_completed' => $result['passage_completed'],
                'passage_index'     => $passageIndex,
                'day'               => $day,
                'plan_id'           => $planId,
            ]);
            $app->close();
        }

        // Day-level toggle (mark all passages at once)
        $completed = CwmprogressHelper::toggleComplete($db, $userId, $planId, $day, max($passageCount, 1));

        $app->sendHeaders();
        echo new JsonResponse([
            'completed' => $completed,
            'day'       => $day,
            'plan_id'   => $planId,
        ]);
        $app->close();
    }
}
