<?php

/**
 * @package    Livingword.Api
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Api\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmnotesHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * The authenticated user's per-day reflection notes.
 *
 * Scoping is inherited from AbstractUserScopedController: every read is
 * constrained to the authenticated user, and no user id is ever read from the
 * request.
 *
 * @since  5.7.0
 */
class NotesController extends AbstractUserScopedController
{
    /**
     * @var    string
     * @since  5.7.0
     */
    protected $contentType = 'notes';

    /**
     * @var    string
     * @since  5.7.0
     */
    protected $default_view = 'notes';

    /**
     * @var    string
     * @since  5.7.0
     */
    protected $modelName = 'Cwmnotes';

    /**
     * Save the authenticated user's note for a day.
     *
     * Upsert rather than create: a user has at most one note per plan-day, so
     * POSTing twice edits rather than duplicating. Saving an empty note deletes
     * it, which is what CwmnotesHelper::saveNote() already does for the site.
     *
     * @return  void
     *
     * @since   5.7.0
     */
    public function add()
    {
        $userId = $this->userId();
        $planId = $this->requestInt('plan_id');
        $day    = $this->requestInt('day');

        if ($planId < 1 || $day < 1) {
            throw new \InvalidArgumentException('plan_id and day are required and must be positive.', 400);
        }

        $body = (array) $this->input->json->get('data', [], 'array');
        $text = (string) ($body['attributes']['note_text'] ?? $body['note_text'] ?? $this->input->getString('note_text', ''));

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $saved = CwmnotesHelper::saveNote($db, $userId, $planId, $day, $text);

        $this->app->setHeader('status', 200);
        $this->app->sendHeaders();

        echo json_encode([
            'data' => [
                'type'       => 'notes',
                'attributes' => [
                    'plan_id' => $planId,
                    'day'     => $day,
                    'saved'   => $saved,
                    'deleted' => trim($text) === '',
                ],
            ],
        ]);

        $this->app->close();
    }
}
