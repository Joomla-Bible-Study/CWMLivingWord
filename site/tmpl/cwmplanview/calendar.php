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
$startDate              = new \DateTime($user->start_date ?: date('Y-01-01'));
$completedDays          = array_flip($data->completedDays ?? []);
$completedPassageCounts = $data->completedPassageCounts ?? [];

/** @var \Joomla\CMS\Document\HtmlDocument $doc */
$wa = $this->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_livingword.main', 'media/com_livingword/css/livingword.css');
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
        <?php
        // Group readings by month
        $months = [];
        $date   = clone $startDate;

        foreach ($readings as $i => $reading) {
            $monthKey = $date->format('Y-m');

            if (!isset($months[$monthKey])) {
                $months[$monthKey] = [
                    'label'    => $date->format('F Y'),
                    'readings' => [],
                ];
            }

            $months[$monthKey]['readings'][] = [
                'day'     => $i + 1,
                'date'    => $date->format('j'),
                'reading' => $reading,
                'current' => ($i + 1) === $data->currentDay,
            ];

            $date->modify('+1 day');
        }
        ?>

        <?php foreach ($months as $month) : ?>
            <h3 class="mb-2"><?php echo $month['label']; ?></h3>
            <div class="row row-cols-4 row-cols-sm-5 row-cols-md-7 livingword-calendar-grid mb-4">
                <?php foreach ($month['readings'] as $entry) : ?>
                    <?php
                    $isDayStarted   = isset($completedDays[$entry['day']]);
                    $passageCount   = CwmprogressHelper::countPassages($entry['reading']->reading);
                    $completedPC    = $completedPassageCounts[$entry['day']] ?? 0;
                    $isFullComplete = $isDayStarted && $completedPC >= $passageCount;
                    $isPartial      = $isDayStarted && $completedPC > 0 && $completedPC < $passageCount;

                    $cardClass = 'livingword-calendar-card';

                    if ($entry['current']) {
                        $cardClass .= ' livingword-calendar-current border-primary';
                    } elseif ($isFullComplete) {
                        $cardClass .= ' border-success';
                    } elseif ($isPartial) {
                        $cardClass .= ' border-warning';
                    }
                    ?>
                    <div class="col">
                        <div class="card <?php echo $cardClass; ?> h-100" data-progress-day="<?php echo $entry['day']; ?>">
                            <div class="card-body p-1 small">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><?php echo $entry['date']; ?></strong>
                                    <?php if ($isFullComplete) : ?>
                                        <span class="icon-checkmark text-success" aria-hidden="true"></span>
                                    <?php elseif ($isPartial) : ?>
                                        <span class="text-warning" style="font-size: 0.65em;"><?php echo $completedPC; ?>/<?php echo $passageCount; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-truncate" style="font-size: 0.7em;">
                                    <?php echo htmlspecialchars($entry['reading']->reading, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
