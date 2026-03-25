<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmprogressHelper;
use CWM\Component\Livingword\Site\Helper\CwmscriptureHelper;
use Joomla\CMS\Language\Text;

/** @var \CWM\Component\Livingword\Site\View\Cwmplanview\HtmlView $this */

$data                   = $this->planData;
$readings               = $data->readings;
$plan                   = $data->planInfo;
$user                   = $data->userData;
$completedDays          = array_flip($data->completedDays ?? []);
$completedPassageCounts = $data->completedPassageCounts ?? [];

/** @var \Joomla\CMS\Document\HtmlDocument $doc */
$wa = $this->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_livingword.main', 'media/com_livingword/css/livingword.css');
?>
<div class="com-livingword-planview">
    <?php echo $this->menu; ?>

    <?php if ($plan) : ?>
        <div class="livingword-plan-header">
            <h2><?php echo $this->escape($plan->description); ?></h2>
        </div>
    <?php endif; ?>

    <?php if ($data->totalDays > 0 && $data->completedCount > 0) : ?>
        <div class="livingword-progress-info mb-4">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="livingword-day-indicator">
                    <?php echo Text::sprintf('COM_LIVINGWORD_PROGRESS_DAYS', $data->completedCount, $data->totalDays); ?>
                </span>
                <span class="badge bg-primary rounded-pill"><?php echo Text::sprintf('COM_LIVINGWORD_PROGRESS_PERCENT', $data->progressPercent); ?></span>
            </div>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-success" style="width: <?php echo $data->progressPercent; ?>%"></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($readings)) : ?>
        <div class="alert alert-info"><?php echo Text::_('COM_LIVINGWORD_NO_READINGS'); ?></div>
    <?php else : ?>
        <table class="table livingword-plan-table">
            <thead>
                <tr>
                    <th class="livingword-check-cell"></th>
                    <th class="livingword-day-cell"><?php echo Text::_('COM_LIVINGWORD_DAY'); ?></th>
                    <th><?php echo Text::_('COM_LIVINGWORD_READING'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($readings as $i => $reading) : ?>
                    <?php
                    $dayNum         = $i + 1;
                    $hasProgress    = isset($completedDays[$dayNum]);
                    $passageCount   = CwmprogressHelper::countPassages($reading->reading);
                    $completedPC    = $completedPassageCounts[$dayNum] ?? 0;
                    $isFullComplete = $hasProgress && $completedPC >= $passageCount;
                    $isPartial      = $hasProgress && $completedPC > 0 && $completedPC < $passageCount;
                    $isCurrent      = $dayNum === $data->currentDay;
                    $rowClass       = $isCurrent ? 'livingword-current-row' : '';
                    ?>
                    <tr<?php echo $rowClass ? ' class="' . $rowClass . '"' : ''; ?> data-progress-day="<?php echo $dayNum; ?>">
                        <td class="livingword-check-cell">
                            <?php if ($isFullComplete) : ?>
                                <span class="icon-checkmark text-success fs-5" aria-label="<?php echo Text::_('COM_LIVINGWORD_COMPLETED'); ?>"></span>
                            <?php elseif ($isPartial) : ?>
                                <span class="badge bg-warning text-dark" aria-label="<?php echo Text::sprintf('COM_LIVINGWORD_PASSAGES_COMPLETED', $completedPC, $passageCount); ?>">
                                    <?php echo $completedPC; ?>/<?php echo $passageCount; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="livingword-day-cell"><?php echo $dayNum; ?></td>
                        <td>
                            <?php echo CwmscriptureHelper::buildReadingLink($reading->reading, $user->bible_version); ?>
                            <?php if (!empty(trim($reading->descrip ?? ''))) : ?>
                                <div class="text-muted small mt-1" style="font-style: italic;">
                                    <?php echo htmlspecialchars(mb_strimwidth(strip_tags($reading->descrip), 0, 120, '...'), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
