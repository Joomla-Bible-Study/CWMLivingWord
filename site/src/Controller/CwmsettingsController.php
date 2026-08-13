<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;

/**
 * Controller for the front-end "My Preferences" form.
 *
 * Handles POST `cwmsettings.save`, which is what
 * `site/tmpl/cwmsettings/default.php` has posted to since the Joomla 5
 * migration — with no controller behind it. Every save answered
 * "Invalid controller class: cwmsettings" with a 404, so no user has ever
 * been able to change their plan, translation, email preferences, timezone
 * or accountability partner from the site. `CwmsettingsModel::saveSettings()`
 * was written at the same time and has been complete and unreachable since.
 *
 * @since  __DEPLOY_VERSION__
 */
class CwmsettingsController extends BaseController
{
    /**
     * Persist the preferences form for the logged-in user.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function save(): void
    {
        $app = Factory::getApplication();

        Session::checkToken('post') || throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);

        $user = $app->getIdentity();

        if ($user === null || (int) $user->id === 0) {
            $app->enqueueMessage(Text::_('COM_LIVINGWORD_SETTINGS_LOGIN_REQUIRED'), 'warning');
            $app->redirect('index.php?option=com_livingword&view=cwmhome');

            return;
        }

        $input = $app->getInput();

        $data = [
            'plan_id'                   => $input->getInt('plan_id', 0),
            'bible_version'             => $input->getCmd('bible_version', 'kjv'),
            'audio_version'             => $input->getCmd('audio_version', ''),
            'email'                     => $input->getInt('email', 0),
            'email_hour'                => $input->getInt('email_hour', 6),
            'timezone'                  => $input->getString('timezone', ''),
            'start_date'                => $input->getString('start_date', ''),
            'date_offset'               => $input->getInt('date_offset', 0),
            'date_offset_day'           => $input->getInt('date_offset_day', 0),
            'action'                    => $input->getCmd('action', ''),
            'accountability_partner_id' => $input->getInt('accountability_partner_id', 0),
            'share_progress'            => $input->getInt('share_progress', 0),
        ];

        /** @var \CWM\Component\Livingword\Site\Model\CwmsettingsModel $model */
        $model = $this->getModel('Cwmsettings');

        // plan_view is not on this form — it is chosen on the plan view itself.
        // saveSettings() writes every column, so without carrying the stored
        // value forward, saving preferences would silently reset the user's
        // reading layout back to the default.
        $current           = $model->getUserSettings();
        $data['plan_view'] = (int) ($current->plan_view ?? 0);

        if ($model->saveSettings($data)) {
            $app->enqueueMessage(Text::_('COM_LIVINGWORD_SETTINGS_SAVED'), 'message');
        } else {
            $app->enqueueMessage(Text::_('COM_LIVINGWORD_SETTINGS_SAVE_FAILED'), 'error');
        }

        $app->redirect('index.php?option=com_livingword&view=cwmsettings');
    }
}
