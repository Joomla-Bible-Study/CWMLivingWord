<?php

/**
 * @package    Livingword.Module
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmscriptureHelper;
use Joomla\CMS\Language\Text;

/** @var object $reading */
?>
<div class="mod-livingword">
    <?php if (!empty($reading->readingText)) : ?>
        <p class="mod-livingword-day">
            <strong><?php echo Text::sprintf('COM_LIVINGWORD_DAY_OF', $reading->currentDay, $reading->totalDays); ?></strong>
        </p>
        <p class="mod-livingword-reading">
            <?php echo CwmscriptureHelper::buildReadingLink($reading->readingText, $reading->bibleversion ?? 'kjv'); ?>
        </p>
    <?php else : ?>
        <p><?php echo Text::_('COM_LIVINGWORD_NO_READING_TODAY'); ?></p>
    <?php endif; ?>
</div>
