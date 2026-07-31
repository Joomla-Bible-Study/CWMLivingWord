<?php

/**
 * @package    Livingword.Api
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Api\View\Progress;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;

/**
 * Reading progress for the authenticated user.
 *
 * @since  5.7.0
 */
class JsonapiView extends BaseApiView
{
    /**
     * @var    array
     * @since  5.7.0
     */
    protected $fieldsToRenderItem = ['id', 'user_id', 'plan_id', 'day', 'passage_index', 'completed_at'];

    /**
     * @var    array
     * @since  5.7.0
     */
    protected $fieldsToRenderList = ['id', 'plan_id', 'day', 'passage_index', 'completed_at'];
}
