<?php

/**
 * @package    Livingword.Module
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Module\Livingword\Site\Dispatcher;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Module\Livingword\Site\Helper\LivingwordHelper;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

/**
 * Dispatcher for mod_livingword
 *
 * @since  5.0.0
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * @return  array
     *
     * @since   5.0.0
     */
    #[\Override]
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();

        /** @var LivingwordHelper $helper */
        $helper = $this->getHelperFactory()->getHelper('LivingwordHelper');

        $data['reading'] = $helper->getTodayReading($data['params']);

        return $data;
    }

    /**
     * The layout renders the component's day-counter and empty-state strings, and the
     * module's own .ini does not carry them. On a page that is not a com_livingword
     * page nothing else loads them, so the reader sees the raw COM_LIVINGWORD_* keys.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    #[\Override]
    protected function loadLanguage(): void
    {
        parent::loadLanguage();

        $this->app->getLanguage()->load('com_livingword', JPATH_SITE);
    }
}
