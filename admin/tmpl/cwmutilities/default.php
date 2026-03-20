<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var \CWM\Component\Livingword\Administrator\View\Cwmutilities\HtmlView $this */

$token = Session::getFormToken();
?>
<div class="row">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><?php echo Text::_('COM_LIVINGWORD_DATABASE_MAINTENANCE'); ?></h3>
            </div>
            <div class="card-body">
                <p><?php echo Text::_('COM_LIVINGWORD_DATABASE_MAINTENANCE_DESC'); ?></p>
                <div class="btn-group" role="group">
                    <a href="<?php echo Route::_('index.php?option=com_livingword&task=cwmutilities.optimize&' . $token . '=1'); ?>" class="btn btn-primary">
                        <span class="icon-refresh" aria-hidden="true"></span>
                        <?php echo Text::_('COM_LIVINGWORD_OPTIMIZE'); ?>
                    </a>
                    <a href="<?php echo Route::_('index.php?option=com_livingword&task=cwmutilities.check&' . $token . '=1'); ?>" class="btn btn-info">
                        <span class="icon-search" aria-hidden="true"></span>
                        <?php echo Text::_('COM_LIVINGWORD_CHECK'); ?>
                    </a>
                    <a href="<?php echo Route::_('index.php?option=com_livingword&task=cwmutilities.repair&' . $token . '=1'); ?>" class="btn btn-warning">
                        <span class="icon-wrench" aria-hidden="true"></span>
                        <?php echo Text::_('COM_LIVINGWORD_REPAIR'); ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><?php echo Text::_('COM_LIVINGWORD_BACKUP'); ?></h3>
            </div>
            <div class="card-body">
                <p><?php echo Text::_('COM_LIVINGWORD_BACKUP_DESC'); ?></p>
                <a href="<?php echo Route::_('index.php?option=com_livingword&task=cwmutilities.backup&' . $token . '=1'); ?>" class="btn btn-success">
                    <span class="icon-download" aria-hidden="true"></span>
                    <?php echo Text::_('COM_LIVINGWORD_BACKUP_DOWNLOAD'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
