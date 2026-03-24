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

/** @var \CWM\Component\Livingword\Site\View\Cwmtools\HtmlView $this */
?>
<div class="com-livingword-tools">
    <?php echo $this->menu; ?>

    <h2><?php echo Text::_('COM_LIVINGWORD_TOOLS'); ?></h2>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title"><?php echo Text::_('COM_LIVINGWORD_TOOLS_DICTIONARY'); ?></h5>
                    <p class="card-text"><?php echo Text::_('COM_LIVINGWORD_TOOLS_DICTIONARY_DESC'); ?></p>
                    <a href="https://www.blueletterbible.org/lexicon/" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary">
                        <?php echo Text::_('COM_LIVINGWORD_TOOLS_OPEN'); ?>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title"><?php echo Text::_('COM_LIVINGWORD_TOOLS_COMMENTARY'); ?></h5>
                    <p class="card-text"><?php echo Text::_('COM_LIVINGWORD_TOOLS_COMMENTARY_DESC'); ?></p>
                    <a href="https://enduringword.com/bible-commentary/" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary">
                        <?php echo Text::_('COM_LIVINGWORD_TOOLS_OPEN'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
