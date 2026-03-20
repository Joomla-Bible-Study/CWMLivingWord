<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\View\Cwmresources;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmmenuHelper;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * Resources view
 *
 * @since  5.0.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var array @since 5.0.0 */
    protected array $resources = [];

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
        $this->resources = $this->getModel()->getResources();
        $this->menu      = CwmmenuHelper::buildMenu();

        parent::display($tpl);
    }
}
