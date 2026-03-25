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
use Joomla\CMS\Language\Text;

/** @var \CWM\Component\Livingword\Site\View\Cwmplanview\HtmlView $this */

$data                   = $this->planData;
$readings               = $data->readings;
$plan                   = $data->planInfo;
$user                   = $data->userData;
$startDate              = new \DateTime($user->start_date ?: date('Y-01-01'));
$completedDays          = array_flip($data->completedDays ?? []);
$completedPassageCounts = $data->completedPassageCounts ?? [];

/** @var \Joomla\CMS\Document\HtmlDocument $doc */
$wa = $this->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_livingword.main', 'media/com_livingword/css/livingword.css');

// Build month data: key = Y-m, value = array of weeks
$months    = [];
$monthKeys = [];
$date      = clone $startDate;

// Day-of-week names
$dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

foreach ($readings as $i => $reading) {
    $monthKey = $date->format('Y-m');

    if (!isset($months[$monthKey])) {
        $months[$monthKey] = [
            'label' => $date->format('F Y'),
            'days'  => [],
            'firstDow' => (int) $date->format('w'), // 0=Sun, 6=Sat
        ];
        $monthKeys[] = $monthKey;
    }

    $months[$monthKey]['days'][] = [
        'day'     => $i + 1,
        'date'    => (int) $date->format('j'),
        'dow'     => (int) $date->format('w'),
        'reading' => $reading,
        'current' => ($i + 1) === $data->currentDay,
    ];

    $date->modify('+1 day');
}

// Find the month containing the current day for initial display
$currentMonthKey = '';

foreach ($months as $key => $month) {
    foreach ($month['days'] as $day) {
        if ($day['current']) {
            $currentMonthKey = $key;

            break 2;
        }
    }
}

if (empty($currentMonthKey)) {
    $currentMonthKey = $monthKeys[0] ?? '';
}
?>
<div class="com-livingword-planview-calendar">
    <?php echo $this->menu; ?>

    <?php if ($plan) : ?>
        <div class="livingword-plan-header">
            <h2><?php echo $this->escape($plan->description); ?></h2>
        </div>
    <?php endif; ?>

    <?php if ($data->totalDays > 0 && ($data->completedCount ?? 0) > 0) : ?>
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

        <?php // ── Month Navigation + Calendar ── ?>
        <?php foreach ($months as $monthKey => $month) : ?>
            <div class="livingword-calendar-month" data-month="<?php echo $monthKey; ?>"
                 style="<?php echo $monthKey !== $currentMonthKey ? 'display:none;' : ''; ?>">

                <?php // Month header with prev/next ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary livingword-cal-prev"
                            data-month="<?php echo $monthKey; ?>"
                            aria-label="<?php echo Text::_('JPREVIOUS'); ?>">
                        <span class="icon-chevron-left" aria-hidden="true"></span>
                    </button>
                    <h3 class="mb-0"><?php echo $month['label']; ?></h3>
                    <button type="button" class="btn btn-sm btn-outline-secondary livingword-cal-next"
                            data-month="<?php echo $monthKey; ?>"
                            aria-label="<?php echo Text::_('JNEXT'); ?>">
                        <span class="icon-chevron-right" aria-hidden="true"></span>
                    </button>
                </div>

                <?php // Day-of-week headers ?>
                <div class="livingword-cal-grid">
                    <?php foreach ($dayNames as $dn) : ?>
                        <div class="livingword-cal-header"><?php echo $dn; ?></div>
                    <?php endforeach; ?>

                    <?php // Blank cells before first day ?>
                    <?php for ($blank = 0; $blank < $month['firstDow']; $blank++) : ?>
                        <div class="livingword-cal-cell livingword-cal-empty"></div>
                    <?php endfor; ?>

                    <?php // Reading days ?>
                    <?php foreach ($month['days'] as $entry) : ?>
                        <?php
                        $isDayStarted   = isset($completedDays[$entry['day']]);
                        $passageCount   = CwmprogressHelper::countPassages($entry['reading']->reading);
                        $completedPC    = $completedPassageCounts[$entry['day']] ?? 0;
                        $isFullComplete = $isDayStarted && $completedPC >= $passageCount;
                        $isPartial      = $isDayStarted && $completedPC > 0 && $completedPC < $passageCount;

                        $cellClass = 'livingword-cal-cell';

                        if ($entry['current']) {
                            $cellClass .= ' livingword-cal-today';
                        } elseif ($isFullComplete) {
                            $cellClass .= ' livingword-cal-complete';
                        } elseif ($isPartial) {
                            $cellClass .= ' livingword-cal-partial';
                        }
                        ?>
                        <div class="<?php echo $cellClass; ?>" data-progress-day="<?php echo $entry['day']; ?>"
                             title="<?php echo Text::sprintf('COM_LIVINGWORD_DAY_OF', $entry['day'], $data->totalDays) . ': ' . $this->escape($entry['reading']->reading); ?>">
                            <div class="livingword-cal-date">
                                <?php echo $entry['date']; ?>
                                <?php if ($isFullComplete) : ?>
                                    <span class="icon-checkmark text-success" aria-hidden="true"></span>
                                <?php elseif ($isPartial) : ?>
                                    <span class="text-warning" style="font-size: 0.6em;"><?php echo $completedPC; ?>/<?php echo $passageCount; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="livingword-cal-reading">
                                <?php echo htmlspecialchars($entry['reading']->reading, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

<?php if (!empty($readings)) : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var months = <?php echo json_encode($monthKeys); ?>;
    var currentIdx = months.indexOf('<?php echo $currentMonthKey; ?>');

    function showMonth(idx) {
        document.querySelectorAll('.livingword-calendar-month').forEach(function(el) {
            el.style.display = 'none';
        });
        var target = document.querySelector('[data-month="' + months[idx] + '"]');
        if (target) target.style.display = '';
    }

    document.querySelectorAll('.livingword-cal-prev').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var idx = months.indexOf(this.dataset.month);
            if (idx > 0) { currentIdx = idx - 1; showMonth(currentIdx); }
        });
    });

    document.querySelectorAll('.livingword-cal-next').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var idx = months.indexOf(this.dataset.month);
            if (idx < months.length - 1) { currentIdx = idx + 1; showMonth(currentIdx); }
        });
    });
});
</script>
<?php endif; ?>
