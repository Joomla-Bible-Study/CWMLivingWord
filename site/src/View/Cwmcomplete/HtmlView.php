<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Site\View\Cwmcomplete;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Reading completion confirmation view.
 *
 * @since  5.3.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var bool @since 5.3.0 */
    protected bool $success = false;

    /** @var int @since 5.3.0 */
    protected int $day = 0;

    /** @var string @since 5.3.0 */
    protected string $reading = '';

    /**
     * @param   string  $tpl  Template name.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   5.3.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        $app           = Factory::getApplication();
        $this->success = (bool) $app->getUserState('com_livingword.complete.success', false);
        $this->day     = (int) $app->getUserState('com_livingword.complete.day', 0);
        $this->reading = (string) $app->getUserState('com_livingword.complete.reading', '');

        $app->setUserState('com_livingword.complete.success', null);
        $app->setUserState('com_livingword.complete.day', null);
        $app->setUserState('com_livingword.complete.reading', null);

        parent::display($tpl);
    }
}
