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
?>
<div class="com-livingword-planview">
    <?php echo $this->menu; ?>

    <?php if ($plan) : ?>
        <h2><?php echo $this->escape($plan->description); ?></h2>
    <?php endif; ?>

    <?php if ($data->totalDays > 0 && $data->completedCount > 0) : ?>
        <div class="livingword-progress-info mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span><?php echo Text::sprintf('COM_LIVINGWORD_PROGRESS_DAYS', $data->completedCount, $data->totalDays); ?></span>
                <span class="badge bg-primary"><?php echo Text::sprintf('COM_LIVINGWORD_PROGRESS_PERCENT', $data->progressPercent); ?></span>
            </div>
            <div class="progress" style="height: 8px;" role="progressbar"
                 aria-valuenow="<?php echo $data->progressPercent; ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-success" style="width: <?php echo $data->progressPercent; ?>%"></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($readings)) : ?>
        <div class="alert alert-info"><?php echo Text::_('COM_LIVINGWORD_NO_READINGS'); ?></div>
    <?php else : ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="w-5 text-center"></th>
                    <th class="w-10"><?php echo Text::_('COM_LIVINGWORD_DAY'); ?></th>
                    <th><?php echo Text::_('COM_LIVINGWORD_READING'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($readings as $i => $reading) : ?>
                    <?php
                    $dayNum        = $i + 1;
                    $hasProgress   = isset($completedDays[$dayNum]);
                    $passageCount  = CwmprogressHelper::countPassages($reading->reading);
                    $completedPC   = $completedPassageCounts[$dayNum] ?? 0;
                    $isFullComplete = $hasProgress && $completedPC >= $passageCount;
                    $isPartial      = $hasProgress && $completedPC > 0 && $completedPC < $passageCount;
                    $rowClass      = '';

                    if ($dayNum === $data->currentDay) {
                        $rowClass = 'table-active fw-bold';
                    } elseif ($isFullComplete) {
                        $rowClass = 'table-success';
                    } elseif ($isPartial) {
                        $rowClass = 'table-warning';
                    }
                    ?>
                    <tr<?php echo $rowClass ? ' class="' . $rowClass . '"' : ''; ?> data-progress-day="<?php echo $dayNum; ?>">
                        <td class="text-center">
                            <?php if ($isFullComplete) : ?>
                                <span class="icon-checkmark text-success" aria-label="<?php echo Text::_('COM_LIVINGWORD_COMPLETED'); ?>"></span>
                            <?php elseif ($isPartial) : ?>
                                <span class="text-warning small" aria-label="<?php echo Text::sprintf('COM_LIVINGWORD_PASSAGES_COMPLETED', $completedPC, $passageCount); ?>">
                                    <?php echo $completedPC; ?>/<?php echo $passageCount; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $dayNum; ?></td>
                        <td>
                            <?php echo CwmscriptureHelper::buildReadingLink($reading->reading, $user->bible_version); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
