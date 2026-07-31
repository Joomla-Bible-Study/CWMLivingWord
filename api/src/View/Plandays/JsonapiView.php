<?php

/**
 * @package    Livingword.Api
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Api\View\Plandays;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;

/**
 * Daily readings within a plan.
 *
 * Fields are listed explicitly rather than serialising the row: a column added
 * later — a token, an internal flag — is then not published by default.
 *
 * @since  5.7.0
 */
class JsonapiView extends BaseApiView
{
    /**
     * @var    array
     * @since  5.7.0
     */
    protected $fieldsToRenderItem = ['id', 'plan_id', 'ordering', 'reading', 'audio', 'descrip'];

    /**
     * @var    array
     * @since  5.7.0
     */
    protected $fieldsToRenderList = ['id', 'plan_id', 'ordering', 'reading'];
}
