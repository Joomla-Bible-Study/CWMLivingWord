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

use CWM\Component\Livingword\Site\Helper\CwmnotesHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

/**
 * AJAX controller for reading notes/journal.
 *
 * @since  5.2.0
 */
class CwmnotesController extends BaseController
{
    /**
     * Save a note for a specific reading day. Returns JSON.
     *
     * @return  void
     *
     * @since   5.2.0
     */
    public function save(): void
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
        $text   = $this->input->getString('note_text', '');

        if ($planId <= 0 || $day <= 0) {
            $app->sendHeaders();
            echo new JsonResponse(null, 'Invalid plan or day', true);
            $app->close();
        }

        $db = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);

        CwmnotesHelper::saveNote($db, $userId, $planId, $day, $text);

        $app->sendHeaders();
        echo new JsonResponse([
            'saved'   => true,
            'plan_id' => $planId,
            'day'     => $day,
        ]);
        $app->close();
    }

    /**
     * Load a note for a specific reading day. Returns JSON.
     *
     * @return  void
     *
     * @since   5.2.0
     */
    public function load(): void
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

        $db   = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
        $note = CwmnotesHelper::getNote($db, $userId, $planId, $day);

        $app->sendHeaders();
        echo new JsonResponse([
            'note_text' => $note ?? '',
            'plan_id'   => $planId,
            'day'       => $day,
        ]);
        $app->close();
    }
}
