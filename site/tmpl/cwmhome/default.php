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
$isLoggedIn  = (int) ($user->user_id ?? 0) > 0;
$isCompleted = $data->isCompleted ?? false;
$progressUrl = Route::_('index.php?option=com_livingword&task=cwmprogress.toggle&format=json', false);

if ($isLoggedIn && $reading) {
    /** @var \Joomla\CMS\Document\HtmlDocument $doc */
    $doc = $this->getDocument();
    $wa  = $doc->getWebAssetManager();
    $wa->registerAndUseScript('com_livingword.progress', 'media/com_livingword/js/livingword-progress.js', [], ['defer' => true]);
    $doc->addScriptOptions('csrf.token', Session::getFormToken());
}

// Audio availability check
$showAudio = $plan && (int) ($plan->audio ?? 0) === 1 && CwmscriptureHelper::isAudioAvailable();

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
                <h3 class="mb-0"><?php echo Text::sprintf('COM_LIVINGWORD_DAY_OF', $data->currentDay, $data->totalDays); ?></h3>
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
                <p class="lead">
                    <?php echo CwmscriptureHelper::buildReadingLink($reading->reading, $user->bible_version); ?>
                </p>
                <?php if (CwmscriptureHelper::isLibraryAvailable()) : ?>
                    <div class="livingword-scripture-text mt-3">
                        <?php echo CwmscriptureHelper::renderReading($reading->reading, $user->bible_version); ?>
                    </div>
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
                <?php if (!empty($reading->descrip)) : ?>
                    <p class="text-muted"><?php echo $this->escape($reading->descrip); ?></p>
                <?php endif; ?>
                <?php if ($isLoggedIn) : ?>
                    <div class="livingword-progress-toggle mt-3"
                         data-livingword-progress
                         data-plan-id="<?php echo (int) ($plan->id ?? 0); ?>"
                         data-day="<?php echo (int) $data->currentDay; ?>"
                         data-completed="<?php echo $isCompleted ? '1' : '0'; ?>"
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
            </div>
        <?php else : ?>
            <div class="alert alert-info"><?php echo Text::_('COM_LIVINGWORD_NO_READING_TODAY'); ?></div>
        <?php endif; ?>

        <div class="livingword-actions mt-3">
            <a href="<?php echo Route::_('index.php?option=com_livingword&view=cwmplanview'); ?>" class="btn btn-outline-primary">
                <?php echo Text::_('COM_LIVINGWORD_VIEW_FULL_PLAN'); ?>
            </a>
        </div>
    </div>
</div>
