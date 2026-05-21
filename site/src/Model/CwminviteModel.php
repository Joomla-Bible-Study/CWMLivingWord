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

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Public group invitation landing page model.
 *
 * Resolves an invite token to its group + plan + leader so unauthenticated
 * visitors can see what they were invited to before logging in or registering.
 *
 * @since  5.8.0
 */
class CwminviteModel extends BaseDatabaseModel
{
    /**
     * Resolve a published group (with plan + leader + member count) by invite token.
     *
     * @param   string  $token  Invite token from the URL
     *
     * @return  ?object  Group details, or null if token is empty / not found / unpublished
     *
     * @since   5.8.0
     */
    public function getGroupByToken(string $token): ?object
    {
        if ($token === '') {
            return null;
        }

        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select([
                $db->quoteName('g.id'),
                $db->quoteName('g.name'),
                $db->quoteName('g.description'),
                $db->quoteName('g.plan_id'),
                $db->quoteName('g.start_date'),
                $db->quoteName('g.invite_token'),
                $db->quoteName('g.join_mode'),
                $db->quoteName('p.title', 'plan_title'),
                $db->quoteName('p.description', 'plan_description'),
                $db->quoteName('p.total_days'),
                '(SELECT COUNT(*) FROM ' . $db->quoteName('#__livingword_group_members') . ' AS mc'
                    . ' WHERE mc.' . $db->quoteName('group_id') . ' = g.' . $db->quoteName('id')
                    . ') AS ' . $db->quoteName('member_count'),
            ])
            ->from($db->quoteName('#__livingword_groups', 'g'))
            ->join('LEFT', $db->quoteName('#__livingword_plans', 'p')
                . ' ON p.' . $db->quoteName('id') . ' = g.' . $db->quoteName('plan_id'))
            ->where($db->quoteName('g.invite_token') . ' = ' . $db->quote($token))
            ->where($db->quoteName('g.published') . ' = 1');

        $db->setQuery($query);
        $group = $db->loadObject();

        if (!$group) {
            return null;
        }

        $group->leader_name = $this->getLeaderName((int) $group->id);

        return $group;
    }

    /**
     * Get the display name of the first group_admin for a group.
     *
     * @param   int  $groupId  Group ID
     *
     * @return  string  Leader display name, or empty string if none
     *
     * @since   5.8.0
     */
    private function getLeaderName(int $groupId): string
    {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
            ->select($db->quoteName('u.name'))
            ->from($db->quoteName('#__livingword_group_members', 'gm'))
            ->join('INNER', $db->quoteName('#__users', 'u')
                . ' ON u.' . $db->quoteName('id') . ' = gm.' . $db->quoteName('user_id'))
            ->where($db->quoteName('gm.group_id') . ' = ' . $groupId)
            ->where($db->quoteName('gm.role') . ' = ' . $db->quote('group_admin'))
            ->order($db->quoteName('gm.joined_at') . ' ASC')
            ->setLimit(1);

        $db->setQuery($query);

        return (string) ($db->loadResult() ?? '');
    }
}
