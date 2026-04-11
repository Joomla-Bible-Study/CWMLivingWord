<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\View\Cwmgroups;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmmenuHelper;
use CWM\Component\Livingword\Site\Helper\CwmuserHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Groups list view
 *
 * @since  5.7.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var array @since 5.7.0 */
    protected array $myGroups = [];

    /** @var array @since 5.7.0 */
    protected array $joinableGroups = [];

    /** @var string @since 5.7.0 */
    protected string $menu = '';

    /**
     * @param   string  $tpl  Template name.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   5.7.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        CwmuserHelper::requireSubscription();

        $model = $this->getModel();

        $this->myGroups       = $model->getMyGroups();
        $this->joinableGroups = $model->getJoinableGroups();
        $this->menu           = CwmmenuHelper::buildMenu();

        $this->prepareDocument();

        parent::display($tpl);
    }

    /**
     * Prepares the document title and metadata.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   5.7.0
     */
    protected function prepareDocument(): void
    {
        $app   = Factory::getApplication();
        $menus = $app->getMenu();
        $menu  = $menus->getActive();

        $title = $menu ? $menu->title : Text::_('COM_LIVINGWORD_GROUPS');

        if ($app->get('sitename_pagetitles', 0) === 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ($app->get('sitename_pagetitles', 0) === 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->getDocument()->setTitle($title);
    }
}
