<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Administrator\View\Cwmgroup;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Administrator\Model\CwmgroupModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * Group edit view
 *
 * @since  5.7.0
 */
class HtmlView extends BaseHtmlView
{
    /**
     * @var mixed
     * @since 5.7.0
     */
    protected mixed $form;

    /**
     * @var ?object
     * @since 5.7.0
     */
    protected ?object $item = null;

    /**
     * @var ?object
     * @since 5.7.0
     */
    protected ?object $state = null;

    /**
     * @var ?object
     * @since 5.7.0
     */
    protected ?object $canDo = null;

    /**
     * @var array
     * @since 5.7.0
     */
    protected array $members = [];

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
        /** @var CwmgroupModel $model */
        $model = $this->getModel();
        $model->setUseExceptions(true);

        $this->form  = $model->getForm();
        $this->item  = $model->getItem();
        $this->state = $model->getState();
        $this->canDo = ContentHelper::getActions('com_livingword');

        if (!empty($this->item->id)) {
            $this->members = $model->getMembers((int) $this->item->id);
        }

        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->setLayout('edit');
        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * @return void
     *
     * @throws \Exception
     * @since 5.7.0
     */
    protected function addToolbar(): void
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);
        $isNew = ((int) $this->item->id === 0);
        $title = $isNew ? Text::_('COM_LIVINGWORD_NEW') : Text::_('COM_LIVINGWORD_EDIT');

        ToolbarHelper::title(Text::_('COM_LIVINGWORD_GROUP') . ': ' . $title, 'users');

        if ($this->canDo->get('core.create') || $this->canDo->get('core.edit')) {
            ToolbarHelper::apply('cwmgroup.apply');
            ToolbarHelper::save('cwmgroup.save');
        }

        ToolbarHelper::cancel('cwmgroup.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}
