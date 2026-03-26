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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var \CWM\Component\Livingword\Site\View\Cwmhome\HtmlView $this */

$data    = $this->homeData;
$reading = $data->todayReading;
$plan    = $data->planInfo;
$user    = $data->userData;

// Progress tracking
$isLoggedIn        = (int) ($user->user_id ?? 0) > 0;
$isCompleted       = $data->isCompleted ?? false;
$passages          = $data->passages ?? [];
$passageCount      = $data->passageCount ?? 1;
$completedPassages = array_flip($data->completedPassages ?? []);
$isMultiPassage    = $passageCount > 1;
$durationType      = $data->durationType ?? 'annual';
$isSelfPaced       = $durationType === 'self_paced';
$progressUrl       = Route::_('index.php?option=com_livingword&task=cwmprogress.toggle&format=json', false);
$hasScripture      = CwmscriptureHelper::isLibraryAvailable();

// Dashboard data
$greetingContext = $data->greetingContext ?? 'guest';
$weeklyProgress  = $data->weeklyProgress ?? 0;
$nextMilestone   = $data->nextMilestone ?? null;
$streakCurrent   = (int) ($user->streak_current ?? 0);
$streakBest      = (int) ($user->streak_best ?? 0);

/** @var \Joomla\CMS\Document\HtmlDocument $doc */
$doc = $this->getDocument();
$wa  = $doc->getWebAssetManager();
$wa->registerAndUseStyle('com_livingword.main', 'media/com_livingword/css/livingword.css');

if ($isLoggedIn && $reading) {
    $wa->registerAndUseScript('com_livingword.progress', 'media/com_livingword/js/livingword-progress.js', [], ['defer' => true]);
    $wa->registerAndUseScript('com_livingword.notes', 'media/com_livingword/js/livingword-notes.js', [], ['defer' => true]);
    $doc->addScriptOptions('csrf.token', Session::getFormToken());
}

$notesUrl = Route::_('index.php?option=com_livingword&task=cwmnotes.save&format=json', false);

// Audio availability check
$audioEnabled = (int) \Joomla\CMS\Component\ComponentHelper::getParams('com_livingword')->get('config_show_audio', 1);
$showAudio    = $audioEnabled && $plan && (int) ($plan->audio ?? 0) === 1 && CwmscriptureHelper::isAudioAvailable();

if ($showAudio && $reading) {
    $audioUrl = Route::_('index.php?option=com_livingword&task=cwmaudio.getAudio&format=json');
    $wa->registerAndUseScript('com_livingword.audio', 'media/com_livingword/js/livingword-audio.js', [], ['defer' => true]);
    $wa->registerAndUseStyle('com_livingword.audio', 'media/com_livingword/css/livingword-audio.css');
    $doc->addScriptOptions('csrf.token', Session::getFormToken());
}
?>
<div class="com-livingword-home">
    <?php echo $this->menu; ?>

    <?php // ── Section 1: Hero / Welcome ── ?>
    <div class="livingword-hero livingword-hero-<?php echo $greetingContext; ?> mb-4">
        <?php if ($greetingContext === 'completed_today') : ?>
            <div class="livingword-hero-inner">
                <h2><?php echo Text::_('COM_LIVINGWORD_COMPLETED_TODAY'); ?></h2>
                <?php if ($plan) : ?>
                    <p class="livingword-hero-sub"><?php echo $this->escape($plan->description); ?></p>
                <?php endif; ?>
            </div>
        <?php elseif ($greetingContext === 'new_user') : ?>
            <div class="livingword-hero-inner">
                <h2><?php echo Text::_('COM_LIVINGWORD_WELCOME_NEW'); ?></h2>
                <?php if ($plan) : ?>
                    <p class="livingword-hero-sub"><?php echo $this->escape($plan->description); ?></p>
                    <?php if (!empty($plan->message)) : ?>
                        <div class="livingword-hero-message"><?php echo $plan->message; ?></div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($reading) : ?>
                    <a href="#todays-reading" class="btn btn-primary livingword-cta mt-2">
                        <?php echo Text::_('COM_LIVINGWORD_START_READING'); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php elseif ($greetingContext === 'returning') : ?>
            <div class="livingword-hero-inner">
                <h2>
                    <?php echo Text::_('COM_LIVINGWORD_WELCOME_BACK'); ?>
                    <?php if ($streakCurrent > 1) : ?>
                        <span class="livingword-streak-badge"><?php echo Text::sprintf('COM_LIVINGWORD_STREAK_CURRENT', $streakCurrent); ?></span>
                    <?php endif; ?>
                </h2>
                <?php if ($plan) : ?>
                    <p class="livingword-hero-sub"><?php echo $this->escape($plan->description); ?></p>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <?php // Guest ?>
            <div class="livingword-hero-inner">
                <?php if ($plan) : ?>
                    <h2><?php echo $this->escape($plan->description); ?></h2>
                    <?php if (!empty($plan->message)) : ?>
                        <div class="livingword-hero-message"><?php echo $plan->message; ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php // ── Section 2: Dashboard Stats Strip ── ?>
    <?php if ($isLoggedIn && $data->totalDays > 0 && $data->completedCount > 0) : ?>
        <div class="livingword-stats-strip mb-4">
            <div class="livingword-stat-item">
                <span class="livingword-stat-value"><?php echo $data->progressPercent; ?>%</span>
                <span class="livingword-stat-label"><?php echo Text::_('COM_LIVINGWORD_PROGRESS_PERCENT_LABEL'); ?></span>
                <div class="progress mt-1" style="height: 4px; width: 100%;">
                    <div class="progress-bar bg-success" style="width: <?php echo $data->progressPercent; ?>%"></div>
                </div>
            </div>
            <div class="livingword-stat-item">
                <span class="livingword-stat-value">
                    <?php echo Text::sprintf($isSelfPaced ? 'COM_LIVINGWORD_READING_OF' : 'COM_LIVINGWORD_DAY_OF', $data->currentDay, $data->totalDays); ?>
                </span>
                <span class="livingword-stat-label"><?php echo Text::sprintf('COM_LIVINGWORD_PROGRESS_DAYS', $data->completedCount, $data->totalDays); ?></span>
            </div>
            <div class="livingword-stat-item">
                <span class="livingword-stat-value"><?php echo $weeklyProgress; ?>/7</span>
                <span class="livingword-stat-label"><?php echo Text::_('COM_LIVINGWORD_THIS_WEEK'); ?></span>
            </div>
            <?php if ($streakCurrent > 0) : ?>
                <div class="livingword-stat-item">
                    <span class="livingword-stat-value"><?php echo $streakCurrent; ?></span>
                    <span class="livingword-stat-label"><?php echo Text::_('COM_LIVINGWORD_STREAK_CURRENT_LABEL'); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <?php // ── Milestone nudge ── ?>
        <?php if ($nextMilestone) : ?>
            <div class="livingword-milestone mb-4">
                <span class="icon-star" aria-hidden="true"></span>
                <?php echo Text::sprintf($nextMilestone->key, ...$nextMilestone->values); ?>
            </div>
        <?php endif; ?>

    <?php elseif (!$isLoggedIn && $data->totalDays > 0) : ?>
        <div class="livingword-stats-strip livingword-stats-guest mb-4">
            <div class="livingword-stat-item">
                <span class="livingword-stat-value">
                    <?php echo Text::sprintf('COM_LIVINGWORD_DAY_OF', $data->currentDay, $data->totalDays); ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <?php // ── Section 3: Today's Reading Card ── ?>
    <?php if ($reading) : ?>
        <div class="card livingword-reading-card mb-4" id="todays-reading">
            <div class="card-body">
                <?php if ($isMultiPassage) : ?>
                    <?php // ── Multi-passage readings ── ?>
                    <div class="livingword-passages">
                        <?php foreach ($passages as $index => $passage) : ?>
                            <?php $passageCompleted = isset($completedPassages[$index]); ?>
                            <div class="livingword-passage-item d-flex align-items-start gap-2"
                                 data-livingword-passage
                                 data-passage-index="<?php echo $index; ?>">
                                <?php if ($isLoggedIn) : ?>
                                    <button type="button"
                                            class="livingword-passage-btn btn btn-sm <?php echo $passageCompleted ? 'btn-success' : 'btn-outline-secondary'; ?> mt-1"
                                            data-livingword-passage-toggle
                                            data-plan-id="<?php echo (int) ($plan->id ?? 0); ?>"
                                            data-day="<?php echo (int) $data->currentDay; ?>"
                                            data-passage-index="<?php echo $index; ?>"
                                            data-passage-count="<?php echo $passageCount; ?>"
                                            data-completed="<?php echo $passageCompleted ? '1' : '0'; ?>"
                                            data-progress-url="<?php echo $this->escape($progressUrl); ?>"
                                            aria-label="<?php echo $passageCompleted ? Text::sprintf('COM_LIVINGWORD_PASSAGE_MARK_UNREAD', $passage) : Text::sprintf('COM_LIVINGWORD_PASSAGE_MARK_READ', $passage); ?>">
                                        <span class="<?php echo $passageCompleted ? 'icon-checkmark' : 'icon-checkbox-unchecked'; ?>" aria-hidden="true"></span>
                                    </button>
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <?php if (!$hasScripture) : ?>
                                        <p class="mb-1 <?php echo $passageCompleted ? 'text-decoration-line-through text-muted' : ''; ?>">
                                            <?php echo CwmscriptureHelper::buildReadingLink($passage, $user->bible_version); ?>
                                        </p>
                                    <?php else : ?>
                                        <div class="livingword-scripture-text <?php echo $passageCompleted ? 'opacity-50' : ''; ?>">
                                            <?php echo CwmscriptureHelper::renderReading($passage, $user->bible_version); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($isLoggedIn) : ?>
                        <hr class="my-3">
                        <div class="d-flex align-items-center justify-content-between"
                             data-livingword-progress
                             data-plan-id="<?php echo (int) ($plan->id ?? 0); ?>"
                             data-day="<?php echo (int) $data->currentDay; ?>"
                             data-completed="<?php echo $isCompleted ? '1' : '0'; ?>"
                             data-passage-count="<?php echo $passageCount; ?>"
                             data-progress-url="<?php echo $this->escape($progressUrl); ?>">
                            <button type="button"
                                    class="livingword-mark-read-btn btn <?php echo $isCompleted ? 'btn-success' : 'btn-outline-secondary'; ?>"
                                    data-label-read="<?php echo Text::_('COM_LIVINGWORD_MARK_ALL_UNREAD'); ?>"
                                    data-label-unread="<?php echo Text::_('COM_LIVINGWORD_MARK_ALL_READ'); ?>"
                                    aria-label="<?php echo $isCompleted ? Text::_('COM_LIVINGWORD_MARK_ALL_UNREAD') : Text::_('COM_LIVINGWORD_MARK_ALL_READ'); ?>">
                                <span class="<?php echo $isCompleted ? 'icon-checkmark' : 'icon-checkbox-unchecked'; ?>" aria-hidden="true"></span>
                                <?php echo $isCompleted ? Text::_('COM_LIVINGWORD_ALL_COMPLETED') : Text::_('COM_LIVINGWORD_MARK_ALL_READ'); ?>
                            </button>
                            <small class="text-muted">
                                <?php echo Text::sprintf('COM_LIVINGWORD_PASSAGES_COMPLETED', \count($data->completedPassages), $passageCount); ?>
                            </small>
                        </div>
                    <?php endif; ?>

                <?php else : ?>
                    <?php // ── Single-passage reading ── ?>
                    <?php if (!$hasScripture) : ?>
                        <p class="lead mb-0">
                            <?php echo CwmscriptureHelper::buildReadingLink($reading->reading, $user->bible_version); ?>
                        </p>
                    <?php else : ?>
                        <div class="livingword-scripture-text">
                            <?php echo CwmscriptureHelper::renderReading($reading->reading, $user->bible_version); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($isLoggedIn) : ?>
                        <hr class="my-3">
                        <div data-livingword-progress
                             data-plan-id="<?php echo (int) ($plan->id ?? 0); ?>"
                             data-day="<?php echo (int) $data->currentDay; ?>"
                             data-completed="<?php echo $isCompleted ? '1' : '0'; ?>"
                             data-passage-count="1"
                             data-progress-url="<?php echo $this->escape($progressUrl); ?>">
                            <button type="button"
                                    class="livingword-mark-read-btn btn <?php echo $isCompleted ? 'btn-success' : 'btn-outline-secondary'; ?>"
                                    data-label-read="<?php echo Text::_('COM_LIVINGWORD_MARK_UNREAD'); ?>"
                                    data-label-unread="<?php echo Text::_('COM_LIVINGWORD_MARK_READ'); ?>"
                                    aria-label="<?php echo $isCompleted ? Text::_('COM_LIVINGWORD_MARK_UNREAD') : Text::_('COM_LIVINGWORD_MARK_READ'); ?>">
                                <span class="<?php echo $isCompleted ? 'icon-checkmark' : 'icon-checkbox-unchecked'; ?>" aria-hidden="true"></span>
                                <?php echo $isCompleted ? Text::_('COM_LIVINGWORD_COMPLETED') : Text::_('COM_LIVINGWORD_MARK_READ'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php // ── Section 4: Study Notes / Devotional ── ?>
        <?php
        $descripText  = trim($reading->descrip ?? '');
        $hasStudyText = !empty($descripText);
        $isLongText   = $hasStudyText && str_word_count(strip_tags($descripText)) > 150;
        ?>
        <?php if ($hasStudyText) : ?>
            <div class="card livingword-study-card mb-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <span class="icon-book" aria-hidden="true"></span>
                        <?php echo Text::_('COM_LIVINGWORD_STUDY_NOTES'); ?>
                    </h5>
                    <?php if ($isLongText) : ?>
                        <div class="livingword-study-content livingword-study-collapsed" id="studyContent">
                            <?php echo $reading->descrip; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-link p-0 mt-2 livingword-study-toggle"
                                onclick="this.closest('.card-body').querySelector('.livingword-study-content').classList.toggle('livingword-study-collapsed'); this.textContent = this.textContent.trim() === '<?php echo Text::_('COM_LIVINGWORD_READ_MORE'); ?>' ? '<?php echo Text::_('COM_LIVINGWORD_READ_LESS'); ?>' : '<?php echo Text::_('COM_LIVINGWORD_READ_MORE'); ?>';">
                            <?php echo Text::_('COM_LIVINGWORD_READ_MORE'); ?>
                        </button>
                    <?php else : ?>
                        <div class="livingword-study-content">
                            <?php echo $reading->descrip; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php // ── Section 5: Audio Player ── ?>
        <?php if ($showAudio) : ?>
            <div class="livingword-audio-section mb-4"
                 data-livingword-audio
                 data-reading="<?php echo $this->escape($reading->reading); ?>"
                 data-version="<?php echo $this->escape($user->bible_version); ?>"
                 data-audio-url="<?php echo $this->escape($audioUrl); ?>">
                <button type="button" class="livingword-audio-play"
                        aria-label="<?php echo Text::_('COM_LIVINGWORD_AUDIO_PLAY'); ?>">
                    <span class="icon-play" aria-hidden="true"></span>
                </button>
                <audio preload="none"></audio>
                <span class="livingword-audio-status d-none"></span>
            </div>
        <?php endif; ?>

        <?php // ── Section 6: Journal / Notes ── ?>
        <?php if ($isLoggedIn) : ?>
            <div class="card livingword-journal-card mb-4"
                 data-livingword-notes
                 data-notes-url="<?php echo $this->escape($notesUrl); ?>"
                 data-plan-id="<?php echo (int) ($plan->id ?? 0); ?>"
                 data-day="<?php echo (int) $data->currentDay; ?>">
                <div class="card-body">
                    <h5 class="card-title">
                        <span class="icon-pencil-2" aria-hidden="true"></span>
                        <?php echo Text::_('COM_LIVINGWORD_MY_JOURNAL'); ?>
                    </h5>
                    <textarea class="form-control livingword-notes-textarea"
                              rows="4"
                              placeholder="<?php echo Text::_('COM_LIVINGWORD_NOTES_PLACEHOLDER'); ?>"
                    ><?php echo $this->escape($data->todayNote ?? ''); ?></textarea>
                    <div class="d-flex justify-content-end mt-1">
                        <span class="livingword-notes-status small text-muted"></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <?php else : ?>
        <div class="alert alert-info"><?php echo Text::_('COM_LIVINGWORD_NO_READING_TODAY'); ?></div>
    <?php endif; ?>

    <?php // ── Section 7: Actions ── ?>
    <div class="livingword-actions mb-4">
        <a href="<?php echo Route::_('index.php?option=com_livingword&view=cwmplanview'); ?>" class="btn btn-outline-primary">
            <?php echo Text::_('COM_LIVINGWORD_VIEW_FULL_PLAN'); ?>
        </a>
    </div>

    <?php // ── Section 8: Partner Progress ── ?>
    <?php
    $partner = $data->partnerProgress ?? null;

    if ($partner && $partner->shares_progress && $partner->is_mutual) : ?>
        <div class="card livingword-partner-card mb-4">
            <div class="card-body">
                <h5 class="card-title"><?php echo Text::sprintf('COM_LIVINGWORD_PARTNER_PROGRESS_TITLE', $this->escape($partner->partner_name)); ?></h5>
                <p class="mb-1 text-muted small">
                    <?php echo $this->escape($partner->plan_name); ?>
                    &mdash; <?php echo Text::sprintf('COM_LIVINGWORD_DAY_OF', $partner->current_day, $partner->total_days); ?>
                </p>
                <?php if ($partner->total_days > 0) : ?>
                    <div class="progress mb-2" style="height: 6px;">
                        <div class="progress-bar bg-info" style="width: <?php echo $partner->progress_percent; ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">
                            <?php echo Text::sprintf('COM_LIVINGWORD_PROGRESS_PERCENT', $partner->progress_percent); ?>
                        </small>
                        <?php if ($partner->streak_current > 0) : ?>
                            <small class="text-muted">
                                <?php echo Text::sprintf('COM_LIVINGWORD_STREAK_CURRENT', $partner->streak_current); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($partner && !$partner->shares_progress) : ?>
        <div class="card livingword-partner-card mb-4">
            <div class="card-body">
                <h5 class="card-title"><?php echo Text::_('COM_LIVINGWORD_PARTNER'); ?></h5>
                <p class="text-muted mb-0">
                    <?php echo Text::sprintf('COM_LIVINGWORD_PARTNER_NOT_SHARING', $this->escape($partner->partner_name)); ?>
                </p>
            </div>
        </div>
    <?php endif; ?>
</div>
