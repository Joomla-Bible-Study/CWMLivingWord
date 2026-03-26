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
$noteDays               = array_flip($data->noteDays ?? []);

// Calculate the date for each reading day based on user start date
$startDate = $user->start_date ?? '';

if (empty($startDate) || $startDate === '0000-00-00') {
    $startDate = date('Y-01-01');
}

$startDt = new \DateTime($startDate);
$offset  = (int) ($user->date_offset ?? 0);

if ($offset !== 0) {
    $startDt->modify(($offset > 0 ? '+' : '') . $offset . ' days');
}

// Group readings by month
$months      = [];
$currentMonth = '';

foreach ($readings as $i => $reading) {
    $dayNum  = $i + 1;
    $dayDate = (clone $startDt)->modify('+' . $i . ' days');
    $monthKey = $dayDate->format('Y-m');
    $monthLabel = $dayDate->format('F Y');

    if (!isset($months[$monthKey])) {
        $months[$monthKey] = [
            'label'    => $monthLabel,
            'readings' => [],
            'completed' => 0,
            'total'    => 0,
        ];
    }

    $hasProgress    = isset($completedDays[$dayNum]);
    $passageCount   = CwmprogressHelper::countPassages($reading->reading);
    $completedPC    = $completedPassageCounts[$dayNum] ?? 0;
    $isFullComplete = $hasProgress && $completedPC >= $passageCount;

    $months[$monthKey]['readings'][] = [
        'reading'         => $reading,
        'dayNum'          => $dayNum,
        'date'            => $dayDate,
        'hasProgress'     => $hasProgress,
        'passageCount'    => $passageCount,
        'completedPC'     => $completedPC,
        'isFullComplete'  => $isFullComplete,
        'isPartial'       => $hasProgress && $completedPC > 0 && $completedPC < $passageCount,
        'isCurrent'       => $dayNum === $data->currentDay,
    ];

    $months[$monthKey]['total']++;

    if ($isFullComplete) {
        $months[$monthKey]['completed']++;
    }
}

// Determine which month contains the current day
$currentMonthKey = '';

foreach ($months as $key => $month) {
    foreach ($month['readings'] as $r) {
        if ($r['isCurrent']) {
            $currentMonthKey = $key;
            break 2;
        }
    }
}

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
        <div class="accordion livingword-month-accordion" id="planMonths">
            <?php foreach ($months as $monthKey => $month) :
                $collapseId = 'month-' . str_replace('-', '', $monthKey);
                $isCurrentMonth = ($monthKey === $currentMonthKey);
                $monthPercent = ($month['total'] > 0) ? round(($month['completed'] / $month['total']) * 100) : 0;
                $allComplete = ($month['completed'] === $month['total']);
            ?>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button<?php echo $isCurrentMonth ? '' : ' collapsed'; ?>"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?php echo $collapseId; ?>"
                                aria-expanded="<?php echo $isCurrentMonth ? 'true' : 'false'; ?>"
                                aria-controls="<?php echo $collapseId; ?>">
                            <span class="d-flex align-items-center justify-content-between w-100 me-2">
                                <span>
                                    <?php if ($allComplete) : ?>
                                        <span class="icon-checkmark text-success me-1"></span>
                                    <?php endif; ?>
                                    <?php echo $this->escape($month['label']); ?>
                                    <span class="text-muted ms-2" style="font-size: 0.85em;">
                                        <?php echo Text::sprintf('COM_LIVINGWORD_MONTH_DAYS', $month['total']); ?>
                                    </span>
                                </span>
                                <?php if ($month['completed'] > 0) : ?>
                                    <span class="badge <?php echo $allComplete ? 'bg-success' : 'bg-secondary'; ?> rounded-pill ms-2">
                                        <?php echo $month['completed']; ?>/<?php echo $month['total']; ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                        </button>
                    </h2>
                    <div id="<?php echo $collapseId; ?>"
                         class="accordion-collapse collapse<?php echo $isCurrentMonth ? ' show' : ''; ?>"
                         data-bs-parent="#planMonths">
                        <div class="accordion-body p-0">
                            <table class="table livingword-plan-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="livingword-check-cell"></th>
                                        <th class="livingword-day-cell"><?php echo Text::_('COM_LIVINGWORD_DAY'); ?></th>
                                        <th><?php echo Text::_('COM_LIVINGWORD_READING'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($month['readings'] as $r) :
                                        $rowClass = $r['isCurrent'] ? 'livingword-current-row' : '';
                                    ?>
                                        <tr<?php echo $rowClass ? ' class="' . $rowClass . '"' : ''; ?>
                                           data-progress-day="<?php echo $r['dayNum']; ?>"
                                           <?php echo $r['isCurrent'] ? ' id="current-reading"' : ''; ?>>
                                            <td class="livingword-check-cell">
                                                <?php if ($r['isFullComplete']) : ?>
                                                    <span class="icon-checkmark text-success fs-5" aria-label="<?php echo Text::_('COM_LIVINGWORD_COMPLETED'); ?>"></span>
                                                <?php elseif ($r['isPartial']) : ?>
                                                    <span class="badge bg-warning text-dark" aria-label="<?php echo Text::sprintf('COM_LIVINGWORD_PASSAGES_COMPLETED', $r['completedPC'], $r['passageCount']); ?>">
                                                        <?php echo $r['completedPC']; ?>/<?php echo $r['passageCount']; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="livingword-day-cell"><?php echo $r['dayNum']; ?></td>
                                            <td>
                                                <?php echo CwmscriptureHelper::buildReadingLink($r['reading']->reading, $user->bible_version); ?>
                                                <?php if (isset($noteDays[$r['dayNum']])) : ?>
                                                    <span class="icon-pencil-2 text-success ms-1" style="font-size: 0.75em;"
                                                          aria-label="<?php echo Text::_('COM_LIVINGWORD_NOTES_INDICATOR'); ?>"
                                                          title="<?php echo Text::_('COM_LIVINGWORD_MY_JOURNAL'); ?>"></span>
                                                <?php endif; ?>
                                                <?php $descripContent = trim($r['reading']->descrip ?? ''); ?>
                                                <?php if (!empty($descripContent)) : ?>
                                                    <?php $collapseStudyId = 'study-day-' . $r['dayNum']; ?>
                                                    <button class="btn btn-sm btn-link p-0 ms-1 livingword-study-toggle"
                                                            type="button"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#<?php echo $collapseStudyId; ?>"
                                                            aria-expanded="false">
                                                        <span class="icon-book" aria-hidden="true" style="font-size: 0.8em;"></span>
                                                        <?php echo Text::_('COM_LIVINGWORD_STUDY_NOTES'); ?>
                                                    </button>
                                                    <div class="collapse mt-2" id="<?php echo $collapseStudyId; ?>">
                                                        <div class="livingword-study-inline">
                                                            <?php echo $r['reading']->descrip; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var el = document.getElementById('current-reading');
                if (el) {
                    setTimeout(function() {
                        el.scrollIntoView({behavior: 'smooth', block: 'center'});
                    }, 300);
                }
            });
        </script>
    <?php endif; ?>
</div>
