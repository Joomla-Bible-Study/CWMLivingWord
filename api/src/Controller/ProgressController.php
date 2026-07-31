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

use CWM\Component\Livingword\Site\Helper\CwmprogressHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * The authenticated user's reading progress.
 *
 * Scoping is inherited from AbstractUserScopedController: every read is
 * constrained to the authenticated user, and no user id is ever read from the
 * request.
 *
 * @since  5.7.0
 */
class ProgressController extends AbstractUserScopedController
{
    /**
     * @var    string
     * @since  5.7.0
     */
    protected $contentType = 'progress';

    /**
     * @var    string
     * @since  5.7.0
     */
    protected $default_view = 'progress';

    /**
     * @var    string
     * @since  5.7.0
     */
    protected $modelName = 'Cwmprogress';

    /**
     * Mark a reading complete for the authenticated user.
     *
     * Idempotent by design: marking a day already complete is a no-op rather
     * than an error, because a mobile client that retries after a dropped
     * connection must not be punished for it.
     *
     * plan_id and day come from the request; the user never does.
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

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // passage_index is optional: a reading with several passages can be
        // ticked off one at a time, or the whole day at once.
        $passageIndex = $this->requestInt('passage_index');

        if ($passageIndex > 0) {
            CwmprogressHelper::markPassageComplete($db, $userId, $planId, $day, $passageIndex);
        } else {
            CwmprogressHelper::markComplete($db, $userId, $planId, $day);
        }

        $this->app->setHeader('status', 201);
        $this->app->sendHeaders();

        echo json_encode([
            'data' => [
                'type'       => 'progress',
                'attributes' => [
                    'plan_id'       => $planId,
                    'day'           => $day,
                    'passage_index' => $passageIndex,
                    'completed'     => true,
                ],
            ],
        ]);

        $this->app->close();
    }
}
