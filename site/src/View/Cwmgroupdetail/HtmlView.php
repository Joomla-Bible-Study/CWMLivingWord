<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\View\Cwmgroupdetail;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmmenuHelper;
use CWM\Component\Livingword\Site\Helper\CwmuserHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Group detail view
 *
 * @since  5.7.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var ?object @since 5.7.0 */
    protected ?object $group = null;

    /** @var array @since 5.7.0 */
    protected array $members = [];

    /** @var string @since 5.7.0 */
    protected string $userRole = '';

    /** @var bool @since 5.7.0 */
    protected bool $canViewProgress = false;

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

        $app     = Factory::getApplication();
        $groupId = $app->getInput()->getInt('group_id', 0);
        $model   = $this->getModel();

        $this->group           = $model->getGroup($groupId);
        $this->members         = $model->getMembers($groupId);
        $this->userRole        = $model->getUserRole($groupId);
        $this->canViewProgress = $model->canViewMemberProgress($groupId);
        $this->menu            = CwmmenuHelper::buildMenu();

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

        $title = $this->group ? $this->group->name : ($menu ? $menu->title : Text::_('COM_LIVINGWORD_GROUP_DETAIL'));

        if ($app->get('sitename_pagetitles', 0) === 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ($app->get('sitename_pagetitles', 0) === 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->getDocument()->setTitle($title);
    }
}
