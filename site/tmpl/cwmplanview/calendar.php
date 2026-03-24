<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmscriptureHelper;
use Joomla\CMS\Language\Text;

/** @var \CWM\Component\Livingword\Site\View\Cwmplanview\HtmlView $this */

$data     = $this->planData;
$readings = $data->readings;
$plan     = $data->planInfo;
$user     = $data->userData;
$startDate     = new \DateTime($user->start_date ?: date('Y-01-01'));
$completedDays = array_flip($data->completedDays ?? []);
?>
<div class="com-livingword-planview-calendar">
    <?php echo $this->menu; ?>

    <?php if ($plan) : ?>
        <h2><?php echo $this->escape($plan->description); ?></h2>
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
            <h3><?php echo $month['label']; ?></h3>
            <div class="row row-cols-7 g-1 mb-3">
                <?php foreach ($month['readings'] as $entry) : ?>
                    <?php $isDayCompleted = isset($completedDays[$entry['day']]); ?>
                    <div class="col">
                        <div class="card<?php echo $entry['current'] ? ' border-primary' : ($isDayCompleted ? ' border-success' : ''); ?> h-100" data-progress-day="<?php echo $entry['day']; ?>">
                            <div class="card-body p-1 small">
                                <strong><?php echo $entry['date']; ?></strong>
                                <?php if ($isDayCompleted) : ?>
                                    <span class="icon-checkmark text-success float-end" aria-hidden="true"></span>
                                <?php endif; ?>
                                <br>
                                <?php
                                echo CwmscriptureHelper::buildReadingLink($entry['reading']->reading, $user->bible_version);
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
