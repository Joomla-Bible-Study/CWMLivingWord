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

$this->getDocument()->getWebAssetManager()
    ->registerAndUseStyle('com_livingword.main', 'media/com_livingword/css/livingword.css');

$tools = [
    [
        'title' => 'COM_LIVINGWORD_TOOLS_DICTIONARY',
        'desc'  => 'COM_LIVINGWORD_TOOLS_DICTIONARY_DESC',
        'url'   => 'https://www.blueletterbible.org/lexicon/',
        'icon'  => 'icon-book',
        'color' => 'text-primary',
    ],
    [
        'title' => 'COM_LIVINGWORD_TOOLS_COMMENTARY',
        'desc'  => 'COM_LIVINGWORD_TOOLS_COMMENTARY_DESC',
        'url'   => 'https://enduringword.com/bible-commentary/',
        'icon'  => 'icon-file-text',
        'color' => 'text-info',
    ],
    [
        'title' => 'COM_LIVINGWORD_TOOLS_CONCORDANCE',
        'desc'  => 'COM_LIVINGWORD_TOOLS_CONCORDANCE_DESC',
        'url'   => 'https://www.blueletterbible.org/search.cfm',
        'icon'  => 'icon-search',
        'color' => 'text-success',
    ],
    [
        'title' => 'COM_LIVINGWORD_TOOLS_MAPS',
        'desc'  => 'COM_LIVINGWORD_TOOLS_MAPS_DESC',
        'url'   => 'https://www.openbible.info/geo/',
        'icon'  => 'icon-location',
        'color' => 'text-warning',
    ],
];
?>
<div class="com-livingword-tools">
    <?php echo $this->menu; ?>

    <div class="livingword-plan-header">
        <h2><?php echo Text::_('COM_LIVINGWORD_TOOLS'); ?></h2>
    </div>

    <div class="row row-cols-1 row-cols-md-2 g-3">
        <?php foreach ($tools as $tool) : ?>
            <div class="col">
                <a href="<?php echo $tool['url']; ?>" target="_blank" rel="noopener noreferrer"
                   class="card h-100 text-decoration-none livingword-resource-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <span class="<?php echo $tool['icon']; ?> fa-2x <?php echo $tool['color']; ?> flex-shrink-0 mt-1" aria-hidden="true"></span>
                            <div>
                                <h5 class="card-title mb-1"><?php echo Text::_($tool['title']); ?></h5>
                                <p class="card-text text-muted mb-2"><?php echo Text::_($tool['desc']); ?></p>
                                <span class="btn btn-sm btn-outline-primary">
                                    <?php echo Text::_('COM_LIVINGWORD_TOOLS_OPEN'); ?>
                                    <span class="icon-out-2 small" aria-hidden="true"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
