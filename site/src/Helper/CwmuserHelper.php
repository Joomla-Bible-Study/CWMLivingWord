<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * User preferences and authentication helper.
 *
 * @since  5.0.0
 */
class CwmuserHelper
{
    /**
     * Get user's LivingWord settings, or component defaults if not set.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  Joomla user ID
     *
     * @return  object  User settings object
     *
     * @since   5.0.0
     */
    public static function getUserData(DatabaseInterface $db, int $userId): object
    {
        $params = ComponentHelper::getParams('com_livingword');

        // Resolve default plan_id from config alias
        $defaultPlanAlias = $params->get('config_bible_plan', 'comp');
        $defaultPlanId    = self::getPlanIdByAlias($db, $defaultPlanAlias);

        $defaults = (object) [
            'id'            => 0,
            'user_id'       => $userId,
            'plan_id'       => $defaultPlanId,
            'bible_version' => $params->get('config_bible_version', 'kjv'),
            'email'         => 0,
            'plan_view'     => 0,
            'start_date'    => $params->get('config_global_startdate', date('Y-m-d')),
            'date_offset'   => 0,
        ];

        if ($userId === 0) {
            return $defaults;
        }

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__livingword_users'))
            ->where($db->quoteName('user_id') . ' = ' . $userId);

        $db->setQuery($query);
        $row = $db->loadObject();

        if ($row) {
            return $row;
        }

        return $defaults;
    }

    /**
     * Save or update user preferences.
     *
     * @param   DatabaseInterface  $db    Database instance
     * @param   object             $data  User settings data
     *
     * @return  bool  True on success
     *
     * @since   5.0.0
     */
    public static function saveUserData(DatabaseInterface $db, object $data): bool
    {
        if (empty($data->user_id)) {
            return false;
        }

        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__livingword_users'))
            ->where($db->quoteName('user_id') . ' = ' . (int) $data->user_id);
        $db->setQuery($query);
        $existingId = $db->loadResult();

        if ($existingId) {
            $data->id = (int) $existingId;
            $db->updateObject('#__livingword_users', $data, 'id');
        } else {
            $db->insertObject('#__livingword_users', $data, 'id');
        }

        return true;
    }

    /**
     * Check if user has access to a specific LivingWord feature.
     *
     * @param   string  $action  The access action (e.g., 'livingword.home')
     * @param   ?int    $userId  User ID (null = current user)
     *
     * @return  bool
     *
     * @throws \Exception
     * @since   5.0.0
     */
    public static function checkAuth(string $action, ?int $userId = null): bool
    {
        $user = $userId !== null
            ? Factory::getContainer()->get(\Joomla\CMS\User\UserFactoryInterface::class)->loadUserById($userId)
            : Factory::getApplication()->getIdentity();

        return $user->authorise($action, 'com_livingword');
    }

    /**
     * Resolve a plan alias to its ID.
     *
     * @param   DatabaseInterface  $db     Database instance
     * @param   string             $alias  Plan alias slug
     *
     * @return  int  Plan ID or 0
     *
     * @since   5.2.0
     */
    public static function getPlanIdByAlias(DatabaseInterface $db, string $alias): int
    {
        if (empty($alias)) {
            return 0;
        }

        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__livingword_plans'))
            ->where($db->quoteName('alias') . ' = ' . $db->quote($alias))
            ->where($db->quoteName('published') . ' = 1');

        $db->setQuery($query);

        return (int) $db->loadResult();
    }
}
