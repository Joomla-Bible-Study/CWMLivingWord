<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmscriptureHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$counts = $this->counts;
$stats  = $this->stats;

// Audio status
$scriptureAvailable = class_exists('CWM\\Library\\Scripture\\LibraryVersion');
$audioAvailable     = false;
$bbKeyConfigured    = false;
$audioPlansCount    = 0;

if ($scriptureAvailable) {
    $audioAvailable  = CwmscriptureHelper::isAudioAvailable();
    $bbKeyConfigured = $audioAvailable;
    $audioPlansCount = $stats->audioPlansCount ?? 0;
}
?>
<style>
.cpanel-icon-card {
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    border: 1px solid var(--template-bg-dark-7, rgba(255,255,255,0.08));
}
.cpanel-icon-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
    border-color: var(--template-link-color, #4f94cd);
}
.cpanel-icon-card:hover h4 {
    color: var(--template-link-color, #4f94cd) !important;
}
.cpanel-icon-card h4 {
    transition: color 0.15s ease;
}
</style>

<div class="com-livingword-cpanel">

    <?php // ── Quick Icon Navigation ── ?>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-5 g-3 mb-4">
        <div class="col">
            <a href="<?php echo Route::_('index.php?option=com_livingword&view=cwmplans'); ?>" class="card cpanel-icon-card text-center text-decoration-none h-100">
                <div class="card-body">
                    <span class="icon-book fa-2x text-primary mb-2 d-block" aria-hidden="true"></span>
                    <h4 class="mb-0"><?php echo $counts['plans'] ?? 0; ?></h4>
                    <small class="text-muted"><?php echo Text::_('COM_LIVINGWORD_MANAGE_PLANS'); ?></small>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="<?php echo Route::_('index.php?option=com_livingword&view=cwmgroups'); ?>" class="card cpanel-icon-card text-center text-decoration-none h-100">
                <div class="card-body">
                    <span class="icon-users fa-2x text-info mb-2 d-block" aria-hidden="true"></span>
                    <h4 class="mb-0"><?php echo $counts['groups'] ?? 0; ?></h4>
                    <small class="text-muted"><?php echo Text::_('COM_LIVINGWORD_MANAGE_GROUPS'); ?></small>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="<?php echo Route::_('index.php?option=com_livingword&view=cwmusers'); ?>" class="card cpanel-icon-card text-center text-decoration-none h-100">
                <div class="card-body">
                    <span class="icon-user fa-2x text-success mb-2 d-block" aria-hidden="true"></span>
                    <h4 class="mb-0"><?php echo $counts['users'] ?? 0; ?></h4>
                    <small class="text-muted"><?php echo Text::_('COM_LIVINGWORD_MANAGE_SUBSCRIBERS'); ?></small>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="<?php echo Route::_('index.php?option=com_livingword&view=cwmlinks'); ?>" class="card cpanel-icon-card text-center text-decoration-none h-100">
                <div class="card-body">
                    <span class="icon-link fa-2x text-warning mb-2 d-block" aria-hidden="true"></span>
                    <h4 class="mb-0"><?php echo $counts['links'] ?? 0; ?></h4>
                    <small class="text-muted"><?php echo Text::_('COM_LIVINGWORD_MANAGE_LINKS'); ?></small>
                </div>
            </a>
        </div>
        <div class="col">
            <a href="<?php echo Route::_('index.php?option=com_livingword&view=cwmutilities'); ?>" class="card cpanel-icon-card text-center text-decoration-none h-100">
                <div class="card-body">
                    <span class="icon-wrench fa-2x text-secondary mb-2 d-block" aria-hidden="true"></span>
                    <h4 class="mb-0">&nbsp;</h4>
                    <small class="text-muted"><?php echo Text::_('COM_LIVINGWORD_UTILITIES'); ?></small>
                </div>
            </a>
        </div>
    </div>

    <?php // ── Audio Status Card ── ?>
    <div class="card mb-4 <?php echo $audioAvailable ? 'border-success' : 'border-warning'; ?>">
        <div class="card-body d-flex align-items-center gap-3">
            <span class="icon-music fa-2x <?php echo $audioAvailable ? 'text-success' : 'text-warning'; ?>" aria-hidden="true"></span>
            <div class="flex-grow-1">
                <h5 class="card-title mb-1"><?php echo Text::_('COM_LIVINGWORD_AUDIO_STATUS'); ?></h5>
                <?php if (!$scriptureAvailable) : ?>
                    <small class="text-muted"><?php echo Text::_('COM_LIVINGWORD_AUDIO_NO_LIBRARY'); ?></small>
                <?php elseif (!$bbKeyConfigured) : ?>
                    <small class="text-warning"><?php echo Text::_('COM_LIVINGWORD_AUDIO_NO_KEY'); ?></small>
                <?php else : ?>
                    <small class="text-success"><?php echo Text::_('COM_LIVINGWORD_AUDIO_CONFIGURED'); ?></small>
                    <span class="ms-2 badge bg-info"><?php echo Text::sprintf('COM_LIVINGWORD_AUDIO_PLANS_ENABLED', $audioPlansCount); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php // ── Congregation Health ── ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><?php echo Text::_('COM_LIVINGWORD_STATS_SUBSCRIBERS'); ?></h5>
                    <div class="d-flex justify-content-around text-center mt-3">
                        <div>
                            <span class="fs-2 fw-bold text-primary"><?php echo $stats->totalSubscribers; ?></span>
                            <br><small class="text-muted"><?php echo Text::_('COM_LIVINGWORD_STATS_TOTAL'); ?></small>
                        </div>
                        <div>
                            <span class="fs-2 fw-bold text-success"><?php echo $stats->activeUsers; ?></span>
                            <br><small class="text-muted"><?php echo Text::_('COM_LIVINGWORD_STATS_ACTIVE_7D'); ?></small>
                        </div>
                        <div>
                            <span class="fs-2 fw-bold <?php echo $stats->inactiveUsers > 0 ? 'text-warning' : 'text-muted'; ?>"><?php echo $stats->inactiveUsers; ?></span>
                            <br><small class="text-muted"><?php echo Text::_('COM_LIVINGWORD_STATS_INACTIVE'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><?php echo Text::_('COM_LIVINGWORD_STATS_PLAN_ENROLLMENT'); ?></h5>
                    <?php if (empty($stats->planEnrollment)) : ?>
                        <p class="text-muted"><?php echo Text::_('COM_LIVINGWORD_STATS_NO_DATA'); ?></p>
                    <?php else : ?>
                        <table class="table table-sm mt-2 mb-0">
                            <tbody>
                                <?php foreach ($stats->planEnrollment as $row) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row->title ?: 'Unknown', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-end fw-bold"><?php echo (int) $row->user_count; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><?php echo Text::_('COM_LIVINGWORD_STATS_AVG_PROGRESS'); ?></h5>
                    <?php if (empty($stats->planProgress)) : ?>
                        <p class="text-muted"><?php echo Text::_('COM_LIVINGWORD_STATS_NO_DATA'); ?></p>
                    <?php else : ?>
                        <?php foreach ($stats->planProgress as $plan) : ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span><?php echo htmlspecialchars($plan->title, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="fw-bold"><?php echo $plan->avg_percent; ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <?php
                                    $barClass = 'bg-success';

                                    if ($plan->avg_percent < 25) {
                                        $barClass = 'bg-danger';
                                    } elseif ($plan->avg_percent < 50) {
                                        $barClass = 'bg-warning';
                                    } elseif ($plan->avg_percent < 75) {
                                        $barClass = 'bg-info';
                                    }
                                    ?>
                                    <div class="progress-bar <?php echo $barClass; ?>" style="width: <?php echo $plan->avg_percent; ?>%"></div>
                                </div>
                                <small class="text-muted"><?php echo $plan->user_count; ?> <?php echo Text::_('COM_LIVINGWORD_STATS_READERS'); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php // ── Group Summaries ── ?>
    <?php if (!empty($stats->groups)) : ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title"><?php echo Text::_('COM_LIVINGWORD_STATS_GROUP_SUMMARIES'); ?></h5>
                <div class="table-responsive">
                    <table class="table table-striped table-sm mt-2 mb-0">
                        <thead>
                            <tr>
                                <th><?php echo Text::_('COM_LIVINGWORD_GROUP_NAME'); ?></th>
                                <th><?php echo Text::_('COM_LIVINGWORD_GROUP_PLAN'); ?></th>
                                <th class="text-center"><?php echo Text::_('COM_LIVINGWORD_GROUP_MEMBER_COUNT'); ?></th>
                                <th><?php echo Text::_('COM_LIVINGWORD_STATS_AVG_PROGRESS'); ?></th>
                                <th class="text-center"><?php echo Text::_('COM_LIVINGWORD_STATS_AVG_STREAK'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats->groups as $group) : ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo Route::_('index.php?option=com_livingword&task=cwmgroup.edit&id=' . (int) $group->id); ?>">
                                            <?php echo htmlspecialchars($group->name, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($group->plan_title ?: '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-center"><?php echo (int) $group->member_count; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-info" style="width: <?php echo $group->avg_progress; ?>%"></div>
                                            </div>
                                            <small class="fw-bold"><?php echo $group->avg_progress; ?>%</small>
                                        </div>
                                    </td>
                                    <td class="text-center"><?php echo $group->avg_streak; ?> <?php echo Text::_('COM_LIVINGWORD_STATS_DAYS'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php // ── Inactive Users ── ?>
    <?php if (!empty($stats->inactiveList)) : ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">
                    <span class="icon-warning text-warning" aria-hidden="true"></span>
                    <?php echo Text::_('COM_LIVINGWORD_STATS_INACTIVE_USERS'); ?>
                </h5>
                <p class="text-muted small"><?php echo Text::_('COM_LIVINGWORD_STATS_INACTIVE_DESC'); ?></p>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th><?php echo Text::_('COM_LIVINGWORD_USER_NAME'); ?></th>
                                <th><?php echo Text::_('COM_LIVINGWORD_STATS_LAST_READ'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats->inactiveList as $user) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if ($user->streak_last_date) : ?>
                                            <?php echo htmlspecialchars($user->streak_last_date, ENT_QUOTES, 'UTF-8'); ?>
                                        <?php else : ?>
                                            <span class="text-muted"><?php echo Text::_('COM_LIVINGWORD_STATS_NEVER'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>
