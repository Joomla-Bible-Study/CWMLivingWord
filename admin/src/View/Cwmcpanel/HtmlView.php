<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Administrator\View\Cwmcpanel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Administrator\Model\CwmcpanelModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Helper\ContentHelper;

/**
 * Dashboard view
 *
 * @since  5.0.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var ?array @since 5.0.0 */
    protected ?array $counts = null;

    /** @var ?object @since 5.0.0 */
    protected ?object $canDo = null;

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
        /** @var CwmcpanelModel $model */
        $model = $this->getModel();
        $model->setUseExceptions(true);

        $this->counts = $model->getCounts();
        $this->canDo  = ContentHelper::getActions('com_livingword');

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * @return void
     *
     * @since 5.0.0
     */
    protected function addToolbar(): void
    {
        ToolbarHelper::title(Text::_('COM_LIVINGWORD') . ' - ' . Text::_('COM_LIVINGWORD_CPANEL'), 'home');

        if ($this->canDo->get('core.admin')) {
            ToolbarHelper::preferences('com_livingword');
        }
    }
}
