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
use Joomla\CMS\Uri\Uri;
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

        // Auto-generate unsubscribe token for new records
        if (empty($data->unsubscribe_token ?? null)) {
            $data->unsubscribe_token = self::generateUnsubscribeToken();
        }

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
     * Generate a random unsubscribe token.
     *
     * @return  string  64-character hex token
     *
     * @since   5.2.0
     */
    public static function generateUnsubscribeToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Build the full unsubscribe URL for a given token.
     *
     * @param   string  $token  The unsubscribe token
     *
     * @return  string  Absolute URL
     *
     * @since   5.2.0
     */
    public static function getUnsubscribeUrl(string $token): string
    {
        return Uri::root() . 'index.php?option=com_livingword&task=cwmunsubscribe.unsubscribe&token=' . urlencode($token);
    }

    /**
     * Unsubscribe a user by their token. No login required.
     *
     * @param   DatabaseInterface  $db     Database instance
     * @param   string             $token  The unsubscribe token
     *
     * @return  bool  True if a matching user was found and unsubscribed
     *
     * @since   5.2.0
     */
    public static function unsubscribeByToken(DatabaseInterface $db, string $token): bool
    {
        if (empty($token) || \strlen($token) !== 64) {
            return false;
        }

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__livingword_users'))
            ->set($db->quoteName('email') . ' = 0')
            ->where($db->quoteName('unsubscribe_token') . ' = ' . $db->quote($token))
            ->where($db->quoteName('email') . ' = 1');

        $db->setQuery($query);
        $db->execute();

        return $db->getAffectedRows() > 0;
    }

    /**
     * Ensure a user has an unsubscribe token, generating one if missing.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  Joomla user ID
     *
     * @return  string  The unsubscribe token
     *
     * @since   5.2.0
     */
    public static function ensureUnsubscribeToken(DatabaseInterface $db, int $userId): string
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('unsubscribe_token'))
            ->from($db->quoteName('#__livingword_users'))
            ->where($db->quoteName('user_id') . ' = ' . $userId);

        $db->setQuery($query);
        $token = $db->loadResult();

        if (!empty($token)) {
            return $token;
        }

        $token = self::generateUnsubscribeToken();

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__livingword_users'))
            ->set($db->quoteName('unsubscribe_token') . ' = ' . $db->quote($token))
            ->where($db->quoteName('user_id') . ' = ' . $userId);

        $db->setQuery($query);
        $db->execute();

        return $token;
    }

    /**
     * Ensure a user has an action token for email-based completion.
     *
     * @param   DatabaseInterface  $db      Database instance
     * @param   int                $userId  Joomla user ID
     *
     * @return  string  The action token
     *
     * @since   5.3.0
     */
    public static function ensureActionToken(DatabaseInterface $db, int $userId): string
    {
        $query = $db->getQuery(true)
            ->select($db->quoteName('action_token'))
            ->from($db->quoteName('#__livingword_users'))
            ->where($db->quoteName('user_id') . ' = ' . $userId);

        $db->setQuery($query);
        $token = $db->loadResult();

        if (!empty($token)) {
            return $token;
        }

        $token = bin2hex(random_bytes(32));

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__livingword_users'))
            ->set($db->quoteName('action_token') . ' = ' . $db->quote($token))
            ->where($db->quoteName('user_id') . ' = ' . $userId);

        $db->setQuery($query);
        $db->execute();

        return $token;
    }

    /**
     * Build the completion URL for a given action token.
     *
     * @param   string  $token  The action token
     *
     * @return  string  Absolute URL
     *
     * @since   5.3.0
     */
    public static function getCompleteReadingUrl(string $token): string
    {
        return Uri::root() . 'index.php?option=com_livingword&task=cwmcomplete.complete&token=' . urlencode($token);
    }

    /**
     * Look up a user by their action token.
     *
     * @param   DatabaseInterface  $db     Database instance
     * @param   string             $token  The action token
     *
     * @return  ?object  User row or null if not found
     *
     * @since   5.3.0
     */
    public static function getUserByActionToken(DatabaseInterface $db, string $token): ?object
    {
        if (empty($token) || \strlen($token) !== 64) {
            return null;
        }

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__livingword_users'))
            ->where($db->quoteName('action_token') . ' = ' . $db->quote($token));

        $db->setQuery($query);

        return $db->loadObject() ?: null;
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
