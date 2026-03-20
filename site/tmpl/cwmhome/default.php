<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmbiblegatewayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Livingword\Site\View\Cwmhome\HtmlView $this */

$data    = $this->homeData;
$reading = $data->todayReading;
$plan    = $data->planInfo;
$user    = $data->userData;
?>
<div class="com-livingword-home">
    <?php echo $this->menu; ?>

    <div class="livingword-reading mt-3">
        <?php if ($plan) : ?>
            <h2><?php echo $this->escape(Text::_($plan->description)); ?></h2>
            <?php if (!empty($plan->message)) : ?>
                <div class="livingword-plan-message mb-3"><?php echo Text::_($plan->message); ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <h3><?php echo Text::sprintf('COM_LIVINGWORD_DAY_OF', $data->currentDay, $data->totalDays); ?></h3>

        <?php if ($reading) : ?>
            <div class="livingword-today-reading">
                <p class="lead">
                    <?php
                    $passageText = CwmbiblegatewayHelper::parseReadingReference($reading->reading);
                    echo CwmbiblegatewayHelper::buildReadingLink($passageText, $user->bibleversion);
                    ?>
                </p>
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
