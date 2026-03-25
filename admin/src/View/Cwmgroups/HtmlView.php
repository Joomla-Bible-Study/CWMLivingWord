<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Administrator\View\Cwmgroups;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Extension\ContentComponent;
use Joomla\Component\Content\Administrator\Helper\ContentHelper;

/**
 * Groups list view
 *
 * @since  5.7.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var ?\Joomla\CMS\Form\Form @since 5.7.0 */
    public ?\Joomla\CMS\Form\Form $filterForm = null;

    /** @var ?array @since 5.7.0 */
    public ?array $activeFilters = null;

    /** @var ?array @since 5.7.0 */
    protected ?array $items = null;

    /** @var ?object @since 5.7.0 */
    protected ?object $pagination = null;

    /** @var ?object @since 5.7.0 */
    protected ?object $state = null;

    /** @var ?object @since 5.7.0 */
    protected ?object $canDo = null;

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
        $model = $this->getModel();
        $model->setUseExceptions(true);

        $this->items         = $model->getItems();
        $this->pagination    = $model->getPagination();
        $this->state         = $model->getState();
        $this->filterForm    = $model->getFilterForm();
        $this->activeFilters = $model->getActiveFilters();
        $this->canDo         = ContentHelper::getActions('com_livingword');

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        if ($this->getLayout() !== 'modal') {
            $this->addToolbar();
        }

        parent::display($tpl);
    }

    /**
     * @return void
     *
     * @since 5.7.0
     */
    protected function addToolbar(): void
    {
        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title(Text::_('COM_LIVINGWORD_MANAGE_GROUPS'), 'users');

        if ($this->canDo->get('core.create')) {
            $toolbar->addNew('cwmgroup.add');
        }

        $dropdown = $toolbar->dropdownButton('status-group')
            ->text('JTOOLBAR_CHANGE_STATUS')
            ->toggleSplit(false)
            ->icon('icon-ellipsis-h')
            ->buttonClass('btn btn-action')
            ->listCheck(true);
        $childBar = $dropdown->getChildToolbar();

        if ($this->canDo->get('core.edit.state')) {
            $childBar->publish('cwmgroups.publish');
            $childBar->unpublish('cwmgroups.unpublish');
            $childBar->checkin('cwmgroups.checkin')->listCheck(true);

            if ((int) $this->state->get('filter.published') !== ContentComponent::CONDITION_TRASHED) {
                $childBar->trash('cwmgroups.trash')->listCheck(true);
            }
        }

        if (
            (int) $this->state->get('filter.published') === ContentComponent::CONDITION_TRASHED
            && $this->canDo->get('core.delete')
        ) {
            $toolbar->delete('cwmgroups.delete')
                ->text('JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }

        if ($this->canDo->get('core.admin')) {
            ToolbarHelper::preferences('com_livingword');
        }
    }
}
