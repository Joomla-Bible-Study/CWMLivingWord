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

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * The authenticated user's LivingWord settings.
 *
 * Scoping is inherited from AbstractUserScopedController: every read is
 * constrained to the authenticated user, and no user id is ever read from the
 * request.
 *
 * @since  5.7.0
 */
class SettingsController extends AbstractUserScopedController
{
    /**
     * @var    string
     * @since  5.7.0
     */
    protected $contentType = 'settings';

    /**
     * @var    string
     * @since  5.7.0
     */
    protected $default_view = 'settings';

    /**
     * @var    string
     * @since  5.7.0
     */
    protected $modelName = 'Cwmsettings';

    /**
     * Fields a client may change.
     *
     * An allowlist, not a denylist. Everything absent is unwritable:
     *
     *   - unsubscribe_token, action_token are credentials. A client that could
     *     set them could choose a token and then act as the user by email link.
     *   - streak_current, streak_best, streak_last_date are earned by reading.
     *     Letting a client post them makes the streak a claim rather than a
     *     record.
     *   - user_id decides whose row this is. It comes from the session.
     *   - accountability_partner_id names another user, and pairing is that
     *     user's decision too, not a field one side can set unilaterally.
     *
     * @var    string[]
     * @since  5.7.0
     */
    private const WRITABLE = [
        'plan_id',
        'bible_version',
        'audio_version',
        'email',
        'plan_view',
        'start_date',
        'date_offset',
        'email_hour',
        'timezone',
        'share_progress',
    ];

    /**
     * Patch the authenticated user's settings.
     *
     * @return  void
     *
     * @since   5.7.0
     */
    public function edit()
    {
        $userId = $this->userId();
        $body   = (array) $this->input->json->get('data', [], 'array');
        $attrs  = (array) ($body['attributes'] ?? $body);

        $changes = array_intersect_key($attrs, array_flip(self::WRITABLE));

        if ($changes === []) {
            throw new \InvalidArgumentException(
                'No writable settings supplied. Writable: ' . implode(', ', self::WRITABLE) . '.',
                400
            );
        }

        $db     = Factory::getContainer()->get(DatabaseInterface::class);
        $query  = $db->getQuery(true)->update($db->quoteName('#__livingword_users'));

        foreach ($changes as $column => $value) {
            $query->set($db->quoteName($column) . ' = ' . $db->quote((string) $value));
        }

        $query->set($db->quoteName('modified') . ' = ' . $db->quote(Factory::getDate()->toSql()))
            ->where($db->quoteName('user_id') . ' = :userId')
            ->bind(':userId', $userId, ParameterType::INTEGER);

        $db->setQuery($query)->execute();

        $this->app->setHeader('status', 200);
        $this->app->sendHeaders();

        echo json_encode([
            'data' => [
                'type'       => 'settings',
                'attributes' => ['updated' => array_keys($changes)],
            ],
        ]);

        $this->app->close();
    }
}
