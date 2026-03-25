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
use Joomla\CMS\Uri\Uri;

/** @var \CWM\Component\Livingword\Site\View\Cwmhome\HtmlView $this */

$data    = $this->homeData;
$reading = $data->todayReading;
$plan    = $data->planInfo;
$user    = $data->userData;

// Progress tracking
$isLoggedIn          = (int) ($user->user_id ?? 0) > 0;
$isCompleted         = $data->isCompleted ?? false;
$passages            = $data->passages ?? [];
$passageCount        = $data->passageCount ?? 1;
$completedPassages   = array_flip($data->completedPassages ?? []);
$isMultiPassage      = $passageCount > 1;
$durationType        = $data->durationType ?? 'annual';
$isSelfPaced         = $durationType === 'self_paced';
$progressUrl         = Route::_('index.php?option=com_livingword&task=cwmprogress.toggle&format=json', false);

if ($isLoggedIn && $reading) {
    /** @var \Joomla\CMS\Document\HtmlDocument $doc */
    $doc = $this->getDocument();
    $wa  = $doc->getWebAssetManager();
    $wa->registerAndUseScript('com_livingword.progress', 'media/com_livingword/js/livingword-progress.js', [], ['defer' => true]);
    $doc->addScriptOptions('csrf.token', Session::getFormToken());
}

// Audio availability check
$audioEnabled = (int) \Joomla\CMS\Component\ComponentHelper::getParams('com_livingword')->get('config_show_audio', 1);
$showAudio    = $audioEnabled && $plan && (int) ($plan->audio ?? 0) === 1 && CwmscriptureHelper::isAudioAvailable();

if ($showAudio && $reading) {
    $audioUrl = Route::_('index.php?option=com_livingword&task=cwmaudio.getAudio&format=json');

    /** @var \Joomla\CMS\Document\HtmlDocument $doc */
    $doc = $this->getDocument();
    $wa  = $doc->getWebAssetManager();
    $wa->registerAndUseScript('com_livingword.audio', 'media/com_livingword/js/livingword-audio.js', [], ['defer' => true]);
    $wa->registerAndUseStyle('com_livingword.audio', 'media/com_livingword/css/livingword-audio.css');

    // Pass CSRF token name for JS fetch calls
    $doc->addScriptOptions('csrf.token', Session::getFormToken());
}
?>
<div class="com-livingword-home">
    <?php echo $this->menu; ?>

    <div class="livingword-reading mt-3">
        <?php if ($plan) : ?>
            <h2><?php echo $this->escape($plan->description); ?></h2>
            <?php if (!empty($plan->message)) : ?>
                <div class="livingword-plan-message mb-3"><?php echo $plan->message; ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="livingword-progress-info mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h3 class="mb-0"><?php echo Text::sprintf($isSelfPaced ? 'COM_LIVINGWORD_READING_OF' : 'COM_LIVINGWORD_DAY_OF', $data->currentDay, $data->totalDays); ?></h3>
                <?php if ($isLoggedIn && $data->totalDays > 0) : ?>
                    <span class="badge bg-primary"><?php echo Text::sprintf('COM_LIVINGWORD_PROGRESS_PERCENT', $data->progressPercent); ?></span>
                <?php endif; ?>
            </div>
            <?php if ($isLoggedIn && $data->totalDays > 0) : ?>
                <div class="progress" style="height: 8px;" role="progressbar"
                     aria-valuenow="<?php echo $data->progressPercent; ?>" aria-valuemin="0" aria-valuemax="100"
                     aria-label="<?php echo Text::sprintf('COM_LIVINGWORD_PROGRESS_PERCENT', $data->progressPercent); ?>">
                    <div class="progress-bar bg-success" style="width: <?php echo $data->progressPercent; ?>%"></div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <small class="text-muted"><?php echo Text::sprintf('COM_LIVINGWORD_PROGRESS_DAYS', $data->completedCount, $data->totalDays); ?></small>
                    <?php if ((int) ($user->streak_current ?? 0) > 0) : ?>
                        <small class="text-muted">
                            <?php echo Text::sprintf('COM_LIVINGWORD_STREAK_CURRENT', (int) $user->streak_current); ?>
                            <?php if ((int) ($user->streak_best ?? 0) > (int) ($user->streak_current ?? 0)) : ?>
                                &middot; <?php echo Text::sprintf('COM_LIVINGWORD_STREAK_BEST', (int) $user->streak_best); ?>
                            <?php endif; ?>
                        </small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($reading) : ?>
            <div class="livingword-today-reading">
                <?php if ($isMultiPassage) : ?>
                    <?php // Multi-passage: show each passage with its own checkmark ?>
                    <div class="livingword-passages">
                        <?php foreach ($passages as $index => $passage) : ?>
                            <?php $passageCompleted = isset($completedPassages[$index]); ?>
                            <div class="livingword-passage-item d-flex align-items-start gap-2 mb-3"
                                 data-livingword-passage
                                 data-passage-index="<?php echo $index; ?>">
                                <?php if ($isLoggedIn) : ?>
                                    <button type="button"
                                            class="btn btn-sm <?php echo $passageCompleted ? 'btn-success' : 'btn-outline-secondary'; ?> livingword-passage-btn flex-shrink-0 mt-1"
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
                                <div class="livingword-passage-content flex-grow-1">
                                    <p class="lead mb-1 <?php echo $passageCompleted ? 'text-decoration-line-through text-muted' : ''; ?>">
                                        <?php echo CwmscriptureHelper::buildReadingLink($passage, $user->bible_version); ?>
                                    </p>
                                    <?php if (CwmscriptureHelper::isLibraryAvailable()) : ?>
                                        <div class="livingword-scripture-text">
                                            <?php echo CwmscriptureHelper::renderReading($passage, $user->bible_version); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($isLoggedIn) : ?>
                        <div class="livingword-progress-toggle mt-3"
                             data-livingword-progress
                             data-plan-id="<?php echo (int) ($plan->id ?? 0); ?>"
                             data-day="<?php echo (int) $data->currentDay; ?>"
                             data-completed="<?php echo $isCompleted ? '1' : '0'; ?>"
                             data-passage-count="<?php echo $passageCount; ?>"
                             data-progress-url="<?php echo $this->escape($progressUrl); ?>">
                            <button type="button"
                                    class="btn <?php echo $isCompleted ? 'btn-success' : 'btn-outline-secondary'; ?> livingword-mark-read-btn"
                                    data-label-read="<?php echo Text::_('COM_LIVINGWORD_MARK_ALL_UNREAD'); ?>"
                                    data-label-unread="<?php echo Text::_('COM_LIVINGWORD_MARK_ALL_READ'); ?>"
                                    aria-label="<?php echo $isCompleted ? Text::_('COM_LIVINGWORD_MARK_ALL_UNREAD') : Text::_('COM_LIVINGWORD_MARK_ALL_READ'); ?>">
                                <span class="<?php echo $isCompleted ? 'icon-checkmark' : 'icon-checkbox-unchecked'; ?>" aria-hidden="true"></span>
                                <?php echo $isCompleted ? Text::_('COM_LIVINGWORD_ALL_COMPLETED') : Text::_('COM_LIVINGWORD_MARK_ALL_READ'); ?>
                            </button>
                            <small class="text-muted ms-2">
                                <?php echo Text::sprintf('COM_LIVINGWORD_PASSAGES_COMPLETED', \count($data->completedPassages), $passageCount); ?>
                            </small>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <?php // Single-passage: original layout ?>
                    <p class="lead">
                        <?php echo CwmscriptureHelper::buildReadingLink($reading->reading, $user->bible_version); ?>
                    </p>
                    <?php if (CwmscriptureHelper::isLibraryAvailable()) : ?>
                        <div class="livingword-scripture-text mt-3">
                            <?php echo CwmscriptureHelper::renderReading($reading->reading, $user->bible_version); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($isLoggedIn) : ?>
                        <div class="livingword-progress-toggle mt-3"
                             data-livingword-progress
                             data-plan-id="<?php echo (int) ($plan->id ?? 0); ?>"
                             data-day="<?php echo (int) $data->currentDay; ?>"
                             data-completed="<?php echo $isCompleted ? '1' : '0'; ?>"
                             data-passage-count="1"
                             data-progress-url="<?php echo $this->escape($progressUrl); ?>">
                            <button type="button"
                                    class="btn <?php echo $isCompleted ? 'btn-success' : 'btn-outline-secondary'; ?> livingword-mark-read-btn"
                                    data-label-read="<?php echo Text::_('COM_LIVINGWORD_MARK_UNREAD'); ?>"
                                    data-label-unread="<?php echo Text::_('COM_LIVINGWORD_MARK_READ'); ?>"
                                    aria-label="<?php echo $isCompleted ? Text::_('COM_LIVINGWORD_MARK_UNREAD') : Text::_('COM_LIVINGWORD_MARK_READ'); ?>">
                                <span class="<?php echo $isCompleted ? 'icon-checkmark' : 'icon-checkbox-unchecked'; ?>" aria-hidden="true"></span>
                                <?php echo $isCompleted ? Text::_('COM_LIVINGWORD_COMPLETED') : Text::_('COM_LIVINGWORD_MARK_READ'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($showAudio) : ?>
                    <div class="livingword-audio-player mt-2"
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
                <?php if (!empty(trim($reading->descrip ?? ''))) : ?>
                    <div class="card mt-3 border-start border-4 border-primary">
                        <div class="card-body">
                            <h5 class="card-title">
                                <span class="icon-quote" aria-hidden="true"></span>
                                <?php echo Text::_('COM_LIVINGWORD_TODAYS_REFLECTION'); ?>
                            </h5>
                            <div class="livingword-devotional" style="font-family: Georgia, 'Times New Roman', serif; line-height: 1.8;">
                                <?php echo $reading->descrip; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="alert alert-info"><?php echo Text::_('COM_LIVINGWORD_NO_READING_TODAY'); ?></div>
        <?php endif; ?>

        <div class="livingword-actions mt-3">
            <a href="<?php echo Route::_('index.php?option=com_livingword&view=cwmplanview'); ?>" class="btn btn-outline-primary">
                <?php echo Text::_('COM_LIVINGWORD_VIEW_FULL_PLAN'); ?>
            </a>
        </div>

        <?php
        $partner = $data->partnerProgress ?? null;
        if ($partner && $partner->shares_progress && $partner->is_mutual) : ?>
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title"><?php echo Text::sprintf('COM_LIVINGWORD_PARTNER_PROGRESS_TITLE', $this->escape($partner->partner_name)); ?></h5>
                    <p class="mb-1">
                        <?php echo $this->escape($partner->plan_name); ?>
                        &mdash; <?php echo Text::sprintf('COM_LIVINGWORD_DAY_OF', $partner->current_day, $partner->total_days); ?>
                    </p>
                    <?php if ($partner->total_days > 0) : ?>
                        <div class="progress mb-2" style="height: 8px;" role="progressbar"
                             aria-valuenow="<?php echo $partner->progress_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-info" style="width: <?php echo $partner->progress_percent; ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">
                                <?php echo Text::sprintf('COM_LIVINGWORD_PROGRESS_DAYS', $partner->completed_count, $partner->total_days); ?>
                                (<?php echo Text::sprintf('COM_LIVINGWORD_PROGRESS_PERCENT', $partner->progress_percent); ?>)
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
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title"><?php echo Text::_('COM_LIVINGWORD_PARTNER'); ?></h5>
                    <p class="text-muted mb-0">
                        <?php echo Text::sprintf('COM_LIVINGWORD_PARTNER_NOT_SHARING', $this->escape($partner->partner_name)); ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
