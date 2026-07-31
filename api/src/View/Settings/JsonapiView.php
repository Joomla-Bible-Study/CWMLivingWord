<?php

/**
 * @package    Livingword.Api
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Api\View\Settings;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;

/**
 * Settings for the authenticated user. The token columns are never selected by the model, so they cannot be rendered here.
 *
 * @since  5.7.0
 */
class JsonapiView extends BaseApiView
{
    /**
     * @var    array
     * @since  5.7.0
     */
    protected $fieldsToRenderItem = ['id', 'user_id', 'plan_id', 'bible_version', 'audio_version', 'email', 'plan_view', 'start_date', 'date_offset', 'streak_current', 'streak_best', 'streak_last_date', 'email_hour', 'timezone', 'share_progress'];

    /**
     * @var    array
     * @since  5.7.0
     */
    protected $fieldsToRenderList = ['id', 'plan_id', 'bible_version', 'plan_view', 'streak_current', 'streak_best', 'email_hour', 'timezone'];
}
