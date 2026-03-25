<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\View\Cwmunsubscribe;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Unsubscribe confirmation view.
 *
 * @since  5.2.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var bool @since 5.2.0 */
    protected bool $success = false;

    /**
     * @param   string  $tpl  Template name.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   5.2.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        $app           = Factory::getApplication();
        $this->success = (bool) $app->getUserState('com_livingword.unsubscribe.success', false);

        // Clear the state so refreshing doesn't re-show success
        $app->setUserState('com_livingword.unsubscribe.success', null);

        parent::display($tpl);
    }
}
