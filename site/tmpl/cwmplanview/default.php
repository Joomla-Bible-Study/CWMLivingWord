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
?>
<div class="com-livingword-planview">
    <?php echo $this->menu; ?>

    <?php if ($plan) : ?>
        <h2><?php echo $this->escape($plan->description); ?></h2>
    <?php endif; ?>

    <?php if (empty($readings)) : ?>
        <div class="alert alert-info"><?php echo Text::_('COM_LIVINGWORD_NO_READINGS'); ?></div>
    <?php else : ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="w-10"><?php echo Text::_('COM_LIVINGWORD_DAY'); ?></th>
                    <th><?php echo Text::_('COM_LIVINGWORD_READING'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($readings as $i => $reading) : ?>
                    <?php $dayNum = $i + 1; ?>
                    <tr<?php echo $dayNum === $data->currentDay ? ' class="table-active fw-bold"' : ''; ?>>
                        <td><?php echo $dayNum; ?></td>
                        <td>
                            <?php echo CwmscriptureHelper::buildReadingLink($reading->reading, $user->bible_version); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
