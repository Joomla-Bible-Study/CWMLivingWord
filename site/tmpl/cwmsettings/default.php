<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Livingword\Site\View\Cwmsettings\HtmlView $this */

$user  = $this->userSettings;
$plans = $this->plans;
?>
<div class="com-livingword-settings">
    <?php echo $this->menu; ?>

    <h2><?php echo Text::_('COM_LIVINGWORD_SETTINGS'); ?></h2>

    <form action="<?php echo Route::_('index.php?option=com_livingword&task=cwmsettings.save'); ?>" method="post" name="settingsForm" id="settingsForm">
        <div class="row">
            <div class="col-lg-6">
                <div class="mb-3">
                    <label for="bibleplan" class="form-label"><?php echo Text::_('COM_LIVINGWORD_SELECT_PLAN'); ?></label>
                    <select name="bibleplan" id="bibleplan" class="form-select">
                        <?php foreach ($plans as $plan) : ?>
                            <option value="<?php echo $this->escape($plan->name); ?>"<?php echo $user->bibleplan === $plan->name ? ' selected' : ''; ?>>
                                <?php echo $this->escape(Text::_($plan->description)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="bibleversion" class="form-label"><?php echo Text::_('COM_LIVINGWORD_SELECT_VERSION'); ?></label>
                    <input type="text" name="bibleversion" id="bibleversion" class="form-control" value="<?php echo $this->escape($user->bibleversion); ?>">
                </div>

                <div class="mb-3">
                    <label for="startdate" class="form-label"><?php echo Text::_('COM_LIVINGWORD_START_DATE'); ?></label>
                    <input type="date" name="startdate" id="startdate" class="form-control" value="<?php echo $this->escape($user->startdate); ?>">
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" name="email" id="email" class="form-check-input" value="1"<?php echo $user->email ? ' checked' : ''; ?>>
                    <label for="email" class="form-check-label"><?php echo Text::_('COM_LIVINGWORD_EMAIL_SUBSCRIBE'); ?></label>
                </div>

                <button type="submit" class="btn btn-primary"><?php echo Text::_('JSAVE'); ?></button>
            </div>
        </div>

        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
