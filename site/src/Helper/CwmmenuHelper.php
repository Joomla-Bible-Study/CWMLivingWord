<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * Frontend menu and UI helper.
 *
 * @since  5.0.0
 */
class CwmmenuHelper
{
    /**
     * Build the frontend navigation menu HTML.
     *
     * @param   ?int  $activeItemId  Active menu item ID
     *
     * @return  string  HTML markup for the menu
     *
     * @throws \Exception
     * @since   5.0.0
     */
    public static function buildMenu(?int $activeItemId = null): string
    {
        $params   = ComponentHelper::getParams('com_livingword');
        $showMenu = (int) $params->get('config_show_menu', 1);

        if (!$showMenu) {
            return '';
        }

        $user   = Factory::getApplication()->getIdentity();
        $itemId = $activeItemId ?: self::getItemId();

        $links = [];

        if ($user->authorise('livingword.home', 'com_livingword')) {
            $links[] = [
                'url'   => Route::_('index.php?option=com_livingword&view=cwmhome&Itemid=' . $itemId),
                'text'  => Text::_('COM_LIVINGWORD_HOME'),
                'view'  => 'cwmhome',
            ];
        }

        if ($user->authorise('livingword.links', 'com_livingword')) {
            $links[] = [
                'url'   => Route::_('index.php?option=com_livingword&view=cwmresources&Itemid=' . $itemId),
                'text'  => Text::_('COM_LIVINGWORD_RESOURCES'),
                'view'  => 'cwmresources',
            ];
        }

        if ($user->authorise('livingword.settings', 'com_livingword')) {
            $links[] = [
                'url'   => Route::_('index.php?option=com_livingword&view=cwmsettings&Itemid=' . $itemId),
                'text'  => Text::_('COM_LIVINGWORD_SETTINGS'),
                'view'  => 'cwmsettings',
            ];
        }

        if ($user->authorise('livingword.tools', 'com_livingword')) {
            $links[] = [
                'url'   => Route::_('index.php?option=com_livingword&view=cwmtools&Itemid=' . $itemId),
                'text'  => Text::_('COM_LIVINGWORD_TOOLS'),
                'view'  => 'cwmtools',
            ];
        }

        if (empty($links)) {
            return '';
        }

        $currentView = Factory::getApplication()->getInput()->getCmd('view', 'cwmhome');

        $html = '<nav class="livingword-menu"><ul class="nav nav-pills">';

        foreach ($links as $link) {
            $active = ($link['view'] === $currentView) ? ' active' : '';
            $html  .= '<li class="nav-item">';
            $html  .= '<a class="nav-link' . $active . '" href="' . $link['url'] . '">' . $link['text'] . '</a>';
            $html  .= '</li>';
        }

        $html .= '</ul></nav>';

        return $html;
    }

    /**
     * Get the active menu Itemid for this component.
     *
     * @return  int
     *
     * @throws \Exception
     * @since   5.0.0
     */
    public static function getItemId(): int
    {
        $app  = Factory::getApplication();
        $menu = $app->getMenu();
        $item = $menu->getActive();

        if ($item && $item->component === 'com_livingword') {
            return (int) $item->id;
        }

        // Find any menu item for com_livingword
        $items = $menu->getItems('component', 'com_livingword');

        if (!empty($items)) {
            return (int) $items[0]->id;
        }

        return (int) ($app->getInput()->getInt('Itemid', 0));
    }
}
