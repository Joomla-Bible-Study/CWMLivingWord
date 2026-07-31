<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

/**
 * A user's own LivingWord settings.
 *
 * A singleton per user — one row in #__livingword_users — but modelled as a
 * ListModel because that is what ApiController::displayList consumes. A client
 * gets a collection of one, or of none before the user has any settings saved.
 *
 * **Two columns are deliberately never selected.** `unsubscribe_token` and
 * `action_token` are credentials, not settings: the first cancels a
 * subscription and the second marks readings complete, both from an emailed
 * link with no further authentication. Publishing either over the API would
 * hand any client that reads a user's settings the ability to act as them by
 * email link. The column list here is the control, so they cannot be exposed
 * by a view that renders whatever it is given.
 *
 * `accountability_partner_id` is also withheld: it names another user, and it
 * is that user's relationship to disclose, not this one's.
 *
 * @since  5.7.0
 */
class CwmsettingsModel extends ListModel
{
    /**
     * Columns safe to return over the API.
     *
     * Listed positively rather than excluding the sensitive ones, so a column
     * added to the table later is withheld until someone decides otherwise.
     *
     * @var    string[]
     * @since  5.7.0
     */
    public const PUBLIC_COLUMNS = [
        'a.id',
        'a.user_id',
        'a.plan_id',
        'a.bible_version',
        'a.audio_version',
        'a.email',
        'a.plan_view',
        'a.start_date',
        'a.date_offset',
        'a.streak_current',
        'a.streak_best',
        'a.streak_last_date',
        'a.email_hour',
        'a.timezone',
        'a.share_progress',
        'a.created',
        'a.modified',
    ];

    /**
     * @param   array  $config  Configuration settings.
     *
     * @throws  \Exception
     * @since   5.7.0
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'a.id', 'plan_id', 'a.plan_id'];
        }

        parent::__construct($config);
    }

    /**
     * Build the query, scoped to the user in state.
     *
     * @return  QueryInterface
     *
     * @since   5.7.0
     */
    protected function getListQuery(): QueryInterface
    {
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        $query->select($db->quoteName(self::PUBLIC_COLUMNS))
            ->from($db->quoteName('#__livingword_users', 'a'));

        // 0 matches no user: a caller that fails to scope reads nothing.
        $userId = (int) $this->getState('filter.user_id', 0);
        $query->where($db->quoteName('a.user_id') . ' = :userId')
            ->bind(':userId', $userId, ParameterType::INTEGER);

        return $query;
    }
}
