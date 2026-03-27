<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\View\Cwmtools;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmmenuHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Study tools view
 *
 * @since  5.0.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var string @since 5.0.0 */
    protected string $menu = '';

    /** @var array @since 5.4.0 */
    protected array $tools = [];

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
        $this->menu  = CwmmenuHelper::buildMenu();
        $this->tools = $this->getModel()->getTools();

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

        $title = $menu ? $menu->title : Text::_('COM_LIVINGWORD_TOOLS');

        if ($app->get('sitename_pagetitles', 0) === 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ($app->get('sitename_pagetitles', 0) === 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->getDocument()->setTitle($title);
    }
}
