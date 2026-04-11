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

use CWM\Component\Livingword\Site\Helper\CwmuserHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

/**
 * Controller for one-click plan subscription from the home onboarding view.
 *
 * Handles POST `cwmsubscribe.start` task: creates a #__livingword_users
 * row with the chosen plan_id + sensible defaults, then redirects back to
 * cwmhome which picks up the new subscription.
 *
 * @since  5.5.0
 */
class CwmsubscribeController extends BaseController
{
    /**
     * Subscribe the current logged-in user to a plan.
     *
     * @return  void
     *
     * @since   5.5.0
     */
    public function start(): void
    {
        $app = Factory::getApplication();

        Session::checkToken('post') || throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);

        $user = $app->getIdentity();

        if ($user === null || (int) $user->id === 0) {
            $app->enqueueMessage(Text::_('COM_LIVINGWORD_SUBSCRIBE_LOGIN_REQUIRED'), 'warning');
            $app->redirect('index.php?option=com_livingword&view=cwmhome');

            return;
        }

        $planId = (int) $app->getInput()->getInt('plan_id', 0);

        if ($planId <= 0) {
            $app->enqueueMessage(Text::_('COM_LIVINGWORD_SUBSCRIBE_INVALID_PLAN'), 'error');
            $app->redirect('index.php?option=com_livingword&view=cwmhome');

            return;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // Verify the plan exists and is published before writing.
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__livingword_plans'))
            ->where($db->quoteName('id') . ' = ' . $planId)
            ->where($db->quoteName('published') . ' = 1');
        $db->setQuery($query);

        if ((int) $db->loadResult() === 0) {
            $app->enqueueMessage(Text::_('COM_LIVINGWORD_SUBSCRIBE_INVALID_PLAN'), 'error');
            $app->redirect('index.php?option=com_livingword&view=cwmhome');

            return;
        }

        $params = ComponentHelper::getParams('com_livingword');

        $settings = (object) [
            'user_id'       => (int) $user->id,
            'plan_id'       => $planId,
            'bible_version' => $params->get('config_bible_version', 'kjv'),
            'email'         => 0,
            'plan_view'     => 0,
            'start_date'    => date('Y-m-d'),
            'date_offset'   => 0,
        ];

        if (CwmuserHelper::saveUserData($db, $settings)) {
            $app->enqueueMessage(Text::_('COM_LIVINGWORD_SUBSCRIBE_SUCCESS'), 'message');
        } else {
            $app->enqueueMessage(Text::_('COM_LIVINGWORD_SUBSCRIBE_FAILED'), 'error');
        }

        $app->redirect('index.php?option=com_livingword&view=cwmhome');
    }
}
