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
/** @var \Joomla\Registry\Registry $params */

$showLink = (int) $params->get('show_reading_link', 1);
?>
<div class="mod-livingword">
    <?php if (empty($reading->hasSubscription)) : ?>
        <p><?php echo Text::_('MOD_LIVINGWORD_NO_PLAN'); ?></p>
    <?php elseif (!empty($reading->readingText)) : ?>
        <p class="mod-livingword-day">
            <strong><?php echo Text::sprintf('COM_LIVINGWORD_DAY_OF', $reading->currentDay, $reading->totalDays); ?></strong>
        </p>
        <?php if ($showLink) : ?>
            <div class="mod-livingword-reading">
                <?php echo CwmscriptureHelper::renderReading($reading->readingText, $reading->bible_version); ?>
            </div>
        <?php else : ?>
            <p class="mod-livingword-reading">
                <?php echo htmlspecialchars($reading->readingText, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>
    <?php else : ?>
        <p><?php echo Text::_('COM_LIVINGWORD_NO_READING_TODAY'); ?></p>
    <?php endif; ?>
</div>
