<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Helper\ContentHelper;

/**
 * LivingWord component helper.
 *
 * @since  5.0.0
 */
class CwmlivingwordHelper
{
    /**
     * Gets a list of the actions that can be performed.
     *
     * @param   string  $section   The section (component, plan, link, etc.)
     * @param   int     $recordId  The record ID
     *
     * @return  object  Object with authorised action properties
     *
     * @since   5.0.0
     */
    public static function getActions(string $section = 'component', int $recordId = 0): object
    {
        return ContentHelper::getActions('com_livingword', $section, $recordId);
    }
}
