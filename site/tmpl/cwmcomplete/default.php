<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Livingword\Site\View\Cwmcomplete\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_livingword.main', 'media/com_livingword/css/livingword.css');
?>
<div class="com-livingword-complete">
    <?php if ($this->success) : ?>
        <div class="alert alert-success">
            <h4 class="alert-heading">
                <span class="icon-checkmark" aria-hidden="true"></span>
                <?php echo Text::_('COM_LIVINGWORD_COMPLETE_SUCCESS'); ?>
            </h4>
            <?php if ($this->day > 0) : ?>
                <p class="mb-0">
                    <?php echo Text::sprintf('COM_LIVINGWORD_COMPLETE_DAY_INFO', $this->day, $this->escape($this->reading)); ?>
                </p>
            <?php endif; ?>
        </div>
        <p>
            <a href="<?php echo Route::_('index.php?option=com_livingword&view=cwmhome'); ?>" class="btn btn-primary">
                <?php echo Text::_('COM_LIVINGWORD_COMPLETE_VISIT_SITE'); ?>
            </a>
        </p>
    <?php else : ?>
        <div class="alert alert-warning">
            <h4><?php echo Text::_('COM_LIVINGWORD_COMPLETE_INVALID'); ?></h4>
        </div>
    <?php endif; ?>
</div>
