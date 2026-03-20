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
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();

        /** @var LivingwordHelper $helper */
        $helper = $this->getHelperFactory()->getHelper('LivingwordHelper');

        $data['reading'] = $helper->getTodayReading($data['params']);

        return $data;
    }
}
