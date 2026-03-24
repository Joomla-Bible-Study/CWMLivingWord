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

        $defaults = (object) [
            'id'           => 0,
            'userid'       => $userId,
            'bibleplan'    => $params->get('config_bible_plan', 'comp'),
            'bibleversion' => $params->get('config_bible_version', 'kjv'),
            'pbversion'    => '',
            'audioversion' => '',
            'email'        => 0,
            'planview'     => 0,
            'readstate'    => 0,
            'startdate'    => $params->get('config_global_startdate', date('Y-m-d')),
            'dateoffset'   => 0,
        ];

        if ($userId === 0) {
            return $defaults;
        }

        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__livingword'))
            ->where($db->quoteName('userid') . ' = ' . $userId);

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
        if (empty($data->userid)) {
            return false;
        }

        // Check if user record exists
        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__livingword'))
            ->where($db->quoteName('userid') . ' = ' . (int) $data->userid);
        $db->setQuery($query);
        $existingId = $db->loadResult();

        if ($existingId) {
            $data->id = (int) $existingId;
            $db->updateObject('#__livingword', $data, 'id');
        } else {
            $db->insertObject('#__livingword', $data, 'id');
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
}
