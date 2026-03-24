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

/** @var \CWM\Component\Livingword\Site\View\Cwmunsubscribe\HtmlView $this */
?>
<div class="com-livingword-unsubscribe">
    <?php if ($this->success) : ?>
        <div class="alert alert-success">
            <h4><?php echo Text::_('COM_LIVINGWORD_UNSUBSCRIBE_SUCCESS'); ?></h4>
        </div>
        <p><?php echo Text::_('COM_LIVINGWORD_UNSUBSCRIBE_RESUBSCRIBE'); ?></p>
    <?php else : ?>
        <div class="alert alert-warning">
            <h4><?php echo Text::_('COM_LIVINGWORD_UNSUBSCRIBE_INVALID'); ?></h4>
        </div>
    <?php endif; ?>
</div>
