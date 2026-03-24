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
 * @since  5.3.0
 */
class CwmprogressController extends BaseController
{
    /**
     * Toggle completion for a reading day. Returns JSON.
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

        $planId = $this->input->getInt('plan_id', 0);
        $day    = $this->input->getInt('day', 0);

        if ($planId <= 0 || $day <= 0) {
            $app->sendHeaders();
            echo new JsonResponse(null, 'Invalid plan or day', true);
            $app->close();
        }

        $db        = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $completed = CwmprogressHelper::toggleComplete($db, $userId, $planId, $day);

        $app->sendHeaders();
        echo new JsonResponse([
            'completed' => $completed,
            'day'       => $day,
            'plan_id'   => $planId,
        ]);
        $app->close();
    }
}
