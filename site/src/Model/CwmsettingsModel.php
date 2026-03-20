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

use CWM\Component\Livingword\Site\Helper\CwmuserHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * User settings model for site frontend.
 *
 * @since  5.0.0
 */
class CwmsettingsModel extends BaseDatabaseModel
{
    /**
     * Get user settings.
     *
     * @return  object
     *
     * @throws \Exception
     * @since   5.0.0
     */
    public function getUserSettings(): object
    {
        $db     = $this->getDatabase();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        return CwmuserHelper::getUserData($db, $userId);
    }

    /**
     * Get available plans for the settings form.
     *
     * @return  array  Array of plan objects
     *
     * @since   5.0.0
     */
    public function getAvailablePlans(): array
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['name', 'description', 'audio', 'newtest']))
            ->from($db->quoteName('#__livingword_plans'))
            ->where($db->quoteName('published') . ' = 1')
            ->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Save user settings from form submission.
     *
     * @param   array  $data  Form data
     *
     * @return  bool
     *
     * @throws \Exception
     * @since   5.0.0
     */
    public function saveSettings(array $data): bool
    {
        $db     = $this->getDatabase();
        $userId = (int) Factory::getApplication()->getIdentity()->id;

        if ($userId === 0) {
            return false;
        }

        $settings = (object) [
            'userid'       => $userId,
            'bibleplan'    => $data['bibleplan'] ?? 'comp',
            'bibleversion' => $data['bibleversion'] ?? 'NLT',
            'pbversion'    => $data['pbversion'] ?? '',
            'audioversion' => $data['audioversion'] ?? '',
            'email'        => (int) ($data['email'] ?? 0),
            'planview'     => (int) ($data['planview'] ?? 0),
            'startdate'    => $data['startdate'] ?? date('Y-m-d'),
            'dateoffset'   => (int) ($data['dateoffset'] ?? 0),
        ];

        return CwmuserHelper::saveUserData($db, $settings);
    }
}
