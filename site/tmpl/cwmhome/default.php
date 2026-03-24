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

        <h3><?php echo Text::sprintf('COM_LIVINGWORD_DAY_OF', $data->currentDay, $data->totalDays); ?></h3>

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
