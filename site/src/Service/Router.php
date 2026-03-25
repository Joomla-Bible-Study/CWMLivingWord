<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\Service;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;

/**
 * Routing class for com_livingword
 *
 * @since  5.0.0
 */
class Router extends RouterView
{
    /**
     * @param   SiteApplication  $app   The application object
     * @param   AbstractMenu     $menu  The menu object to work with
     *
     * @since   5.0.0
     */
    public function __construct(SiteApplication $app, AbstractMenu $menu)
    {
        // Home / main reading
        $home = new RouterViewConfiguration('cwmhome');
        $this->registerView($home);

        // Full plan view
        $planview = new RouterViewConfiguration('cwmplanview');
        $planview->setParent($home);
        $this->registerView($planview);

        // Resources
        $resources = new RouterViewConfiguration('cwmresources');
        $resources->setParent($home);
        $this->registerView($resources);

        // User settings
        $settings = new RouterViewConfiguration('cwmsettings');
        $settings->setParent($home);
        $this->registerView($settings);

        // Tools
        $tools = new RouterViewConfiguration('cwmtools');
        $tools->setParent($home);
        $this->registerView($tools);

        // Groups
        $groups = new RouterViewConfiguration('cwmgroups');
        $groups->setParent($home);
        $this->registerView($groups);

        // Group detail
        $groupdetail = new RouterViewConfiguration('cwmgroupdetail');
        $groupdetail->setParent($groups);
        $groupdetail->setKey('id');
        $this->registerView($groupdetail);

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }
}
