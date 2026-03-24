<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\View\Cwmplanview;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmmenuHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Full plan view (list or calendar layout)
 *
 * @since  5.0.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var ?object @since 5.0.0 */
    protected ?object $planData = null;

    /** @var string @since 5.0.0 */
    protected string $menu = '';

    /**
     * @param   string  $tpl  Template name.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   5.0.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        $this->planData = $this->getModel()->getPlanViewData();
        $this->menu     = CwmmenuHelper::buildMenu();

        // Determine layout from user preference or config
        $planView = (int) ($this->planData->userData->planview ?? 0);

        if ($planView === 2) {
            $this->setLayout('calendar');
        } else {
            $params      = ComponentHelper::getParams('com_livingword');
            $planTmpl    = $params->get('config_plan_template', 'default');

            if ($planTmpl === 'calendar') {
                $this->setLayout('calendar');
            }
        }

        $this->prepareDocument();

        parent::display($tpl);
    }

    /**
     * Prepares the document title and metadata.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   5.0.0
     */
    protected function prepareDocument(): void
    {
        $app   = Factory::getApplication();
        $menus = $app->getMenu();
        $menu  = $menus->getActive();

        $title = $menu ? $menu->title : Text::_('COM_LIVINGWORD_VIEW_FULL_PLAN');

        if ($app->get('sitename_pagetitles', 0) === 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ($app->get('sitename_pagetitles', 0) === 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->getDocument()->setTitle($title);
    }
}
