<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmreadingHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Livingword\Site\View\Cwmsettings\HtmlView $this */

$user     = $this->userSettings;
$plans    = $this->plans;
$partners = $this->availablePartners;

$this->getDocument()->getWebAssetManager()
    ->registerAndUseStyle('com_livingword.main', 'media/com_livingword/css/livingword.css');

// Calculate current position for display
$planId     = (int) ($user->plan_id ?? 0);
$totalDays  = 0;
$currentDay = 0;

if ($planId > 0) {
    $db         = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
    $totalDays  = CwmreadingHelper::getPlanTotalDays($db, $planId);
    $currentDay = CwmreadingHelper::getCurrentReadingDay($user->start_date ?? '', (int) ($user->date_offset ?? 0), $totalDays ?: 365);
}

$versions = [
    'kjv'  => 'KJV',
    'nkjv' => 'NKJV',
    'niv'  => 'NIV',
    'esv'  => 'ESV',
    'nlt'  => 'NLT',
    'nasb' => 'NASB',
    'amp'  => 'AMP',
    'csb'  => 'CSB',
    'web'  => 'WEB',
    'msg'  => 'MSG',
];
?>
<div class="com-livingword-settings">
    <?php echo $this->menu; ?>

    <div class="livingword-plan-header">
        <h2><?php echo Text::_('COM_LIVINGWORD_SETTINGS'); ?></h2>
    </div>

    <form action="<?php echo Route::_('index.php?option=com_livingword&task=cwmsettings.save'); ?>" method="post" name="settingsForm" id="settingsForm">
        <div class="row g-4">
            <?php // ── Left Column: Reading Preferences ── ?>
            <div class="col-lg-6">
                <div class="card livingword-reading-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <span class="icon-book" aria-hidden="true"></span>
                            <?php echo Text::_('COM_LIVINGWORD_SETTINGS_READING'); ?>
                        </h5>

                        <div class="mb-3">
                            <label for="plan_id" class="form-label"><?php echo Text::_('COM_LIVINGWORD_SELECT_PLAN'); ?></label>
                            <select name="plan_id" id="plan_id" class="form-select">
                                <?php foreach ($plans as $plan) : ?>
                                    <option value="<?php echo (int) $plan->id; ?>"<?php echo (int) $user->plan_id === (int) $plan->id ? ' selected' : ''; ?>>
                                        <?php echo $this->escape($plan->title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="bible_version" class="form-label"><?php echo Text::_('COM_LIVINGWORD_SELECT_VERSION'); ?></label>
                            <select name="bible_version" id="bible_version" class="form-select">
                                <?php foreach ($versions as $code => $label) : ?>
                                    <option value="<?php echo $code; ?>"<?php echo ($user->bible_version ?? 'kjv') === $code ? ' selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="audio_version" class="form-label"><?php echo Text::_('COM_LIVINGWORD_AUDIO_VERSION'); ?></label>
                            <select name="audio_version" id="audio_version" class="form-select">
                                <option value=""><?php echo Text::_('COM_LIVINGWORD_AUDIO_VERSION_DEFAULT'); ?></option>
                                <?php foreach ($versions as $code => $label) : ?>
                                    <option value="<?php echo $code; ?>"<?php echo ($user->audio_version ?? '') === $code ? ' selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted"><?php echo Text::_('COM_LIVINGWORD_AUDIO_VERSION_DESC'); ?></small>
                        </div>

                        <div class="mb-0">
                            <label for="start_date" class="form-label"><?php echo Text::_('COM_LIVINGWORD_START_DATE'); ?></label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $this->escape($user->start_date); ?>">
                        </div>
                    </div>
                </div>

                <div class="card livingword-reading-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <span class="icon-mail" aria-hidden="true"></span>
                            <?php echo Text::_('COM_LIVINGWORD_SETTINGS_EMAIL'); ?>
                        </h5>

                        <div class="mb-3 form-check">
                            <input type="checkbox" name="email" id="email" class="form-check-input" value="1"<?php echo $user->email ? ' checked' : ''; ?>>
                            <label for="email" class="form-check-label"><?php echo Text::_('COM_LIVINGWORD_EMAIL_SUBSCRIBE'); ?></label>
                        </div>

                        <div class="mb-3">
                            <label for="email_hour" class="form-label"><?php echo Text::_('COM_LIVINGWORD_EMAIL_HOUR'); ?></label>
                            <select name="email_hour" id="email_hour" class="form-select">
                                <?php for ($h = 0; $h < 24; $h++) : ?>
                                    <?php $label = ($h === 0) ? '12:00 AM' : (($h < 12) ? $h . ':00 AM' : (($h === 12) ? '12:00 PM' : ($h - 12) . ':00 PM')); ?>
                                    <option value="<?php echo $h; ?>"<?php echo (int) ($user->email_hour ?? 6) === $h ? ' selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="mb-0">
                            <label for="timezone" class="form-label"><?php echo Text::_('COM_LIVINGWORD_TIMEZONE'); ?></label>
                            <select name="timezone" id="timezone" class="form-select">
                                <option value=""><?php echo Text::_('COM_LIVINGWORD_TIMEZONE_DEFAULT'); ?></option>
                                <?php foreach (\DateTimeZone::listIdentifiers() as $tz) : ?>
                                    <option value="<?php echo $tz; ?>"<?php echo ($user->timezone ?? '') === $tz ? ' selected' : ''; ?>>
                                        <?php echo str_replace(['/', '_'], [' / ', ' '], $tz); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <?php // ── Right Column: Position, Streaks, Partner ── ?>
            <div class="col-lg-6">
                <?php if ($totalDays > 0) : ?>
                    <div class="card livingword-reading-card mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <span class="icon-compass" aria-hidden="true"></span>
                                <?php echo Text::_('COM_LIVINGWORD_READING_POSITION'); ?>
                            </h5>
                            <p class="mb-2">
                                <span class="livingword-day-indicator">
                                    <?php echo Text::sprintf('COM_LIVINGWORD_POSITION_INFO', $currentDay, $totalDays); ?>
                                </span>
                            </p>

                            <div class="mb-3">
                                <label for="date_offset_day" class="form-label"><?php echo Text::_('COM_LIVINGWORD_JUMP_TO_DAY'); ?></label>
                                <div class="input-group">
                                    <input type="number" name="date_offset_day" id="date_offset_day" class="form-control"
                                           min="1" max="<?php echo $totalDays; ?>" value="<?php echo $currentDay; ?>">
                                    <span class="input-group-text"><?php echo Text::sprintf('COM_LIVINGWORD_OF_DAYS', $totalDays); ?></span>
                                </div>
                                <small class="text-muted"><?php echo Text::_('COM_LIVINGWORD_JUMP_TO_DAY_DESC'); ?></small>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" name="action" value="skip_to_today" class="btn btn-sm btn-outline-warning livingword-mark-read-btn">
                                    <?php echo Text::_('COM_LIVINGWORD_SKIP_TO_TODAY'); ?>
                                </button>
                                <button type="submit" name="action" value="restart" class="btn btn-sm btn-outline-secondary livingword-mark-read-btn">
                                    <?php echo Text::_('COM_LIVINGWORD_RESTART_PLAN'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php if ((int) ($user->streak_current ?? 0) > 0 || (int) ($user->streak_best ?? 0) > 0) : ?>
                        <div class="card livingword-reading-card mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <span class="icon-fire" aria-hidden="true"></span>
                                    <?php echo Text::_('COM_LIVINGWORD_STREAKS'); ?>
                                </h5>
                                <div class="d-flex gap-5 text-center">
                                    <div>
                                        <span class="fs-2 fw-bold text-primary"><?php echo (int) ($user->streak_current ?? 0); ?></span>
                                        <br><small class="text-muted"><?php echo Text::_('COM_LIVINGWORD_STREAK_CURRENT_LABEL'); ?></small>
                                    </div>
                                    <div>
                                        <span class="fs-2 fw-bold text-success"><?php echo (int) ($user->streak_best ?? 0); ?></span>
                                        <br><small class="text-muted"><?php echo Text::_('COM_LIVINGWORD_STREAK_BEST_LABEL'); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="card livingword-partner-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-2">
                            <span class="icon-users" aria-hidden="true"></span>
                            <?php echo Text::_('COM_LIVINGWORD_PARTNER'); ?>
                        </h5>
                        <p class="text-muted small mb-3"><?php echo Text::_('COM_LIVINGWORD_PARTNER_DESC'); ?></p>

                        <div class="mb-3">
                            <label for="accountability_partner_id" class="form-label"><?php echo Text::_('COM_LIVINGWORD_PARTNER_SELECT'); ?></label>
                            <select name="accountability_partner_id" id="accountability_partner_id" class="form-select">
                                <option value=""><?php echo Text::_('COM_LIVINGWORD_PARTNER_NONE'); ?></option>
                                <?php foreach ($partners as $partner) : ?>
                                    <option value="<?php echo (int) $partner->id; ?>"<?php echo (int) ($user->accountability_partner_id ?? 0) === (int) $partner->id ? ' selected' : ''; ?>>
                                        <?php echo $this->escape($partner->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-0 form-check">
                            <input type="checkbox" name="share_progress" id="share_progress" class="form-check-input" value="1"<?php echo (int) ($user->share_progress ?? 0) ? ' checked' : ''; ?>>
                            <label for="share_progress" class="form-check-label"><?php echo Text::_('COM_LIVINGWORD_PARTNER_SHARE'); ?></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="date_offset" id="date_offset" value="<?php echo (int) ($user->date_offset ?? 0); ?>">
        <button type="submit" class="btn btn-primary livingword-mark-read-btn">
            <span class="icon-save" aria-hidden="true"></span>
            <?php echo Text::_('JSAVE'); ?>
        </button>

        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
