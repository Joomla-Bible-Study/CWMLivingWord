<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;

/** @var \CWM\Component\Livingword\Site\View\Cwmresources\HtmlView $this */
?>
<div class="com-livingword-resources">
    <?php echo $this->menu; ?>

    <h2><?php echo Text::_('COM_LIVINGWORD_RESOURCES'); ?></h2>

    <?php if (empty($this->resources)) : ?>
        <div class="alert alert-info"><?php echo Text::_('COM_LIVINGWORD_NO_RESOURCES'); ?></div>
    <?php else : ?>
        <?php foreach ($this->resources as $category => $links) : ?>
            <h3><?php echo $this->escape($category); ?></h3>
            <ul class="list-group mb-3">
                <?php foreach ($links as $link) : ?>
                    <li class="list-group-item">
                        <a href="<?php echo $this->escape($link->url); ?>"
                           <?php echo $link->target == 2 ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                            <?php echo $this->escape($link->name); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
