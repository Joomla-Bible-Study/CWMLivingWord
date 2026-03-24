<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Livingword\Administrator\View\Cwmplandetail\HtmlView $this */
?>
<form action="<?php echo Route::_('index.php?option=com_livingword&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="main-card">
        <div class="row">
            <div class="col-lg-9">
                <?php echo $this->form->renderField('plan'); ?>
                <?php echo $this->form->renderField('reading'); ?>
                <?php echo $this->form->renderField('descrip'); ?>
                <?php echo $this->form->renderField('audio'); ?>
            </div>
            <div class="col-lg-3">
                <?php echo $this->form->renderField('ordering'); ?>
                <?php echo $this->form->renderField('id'); ?>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
