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
?>
<div class="com-livingword-tools">
    <?php echo $this->menu; ?>

    <div class="livingword-plan-header">
        <h2><?php echo Text::_('COM_LIVINGWORD_TOOLS'); ?></h2>
    </div>

    <?php if (empty($this->tools)) : ?>
        <div class="alert alert-info">
            <?php echo Text::_('COM_LIVINGWORD_NO_TOOLS'); ?>
        </div>
    <?php else : ?>
        <?php foreach ($this->tools as $categoryTitle => $tools) : ?>
            <?php if ($categoryTitle !== '') : ?>
                <h3 class="mt-4 mb-3"><?php echo $this->escape($categoryTitle); ?></h3>
            <?php endif; ?>
            <div class="row row-cols-1 row-cols-md-2 g-3 mb-3">
                <?php foreach ($tools as $tool) : ?>
                    <div class="col">
                        <a href="<?php echo $this->escape($tool['url']); ?>" target="_blank" rel="noopener noreferrer"
                           class="card h-100 text-decoration-none livingword-resource-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start gap-3">
                                    <?php if (!empty($tool['icon'])) : ?>
                                        <span class="<?php echo $this->escape($tool['icon']); ?> fa-2x <?php echo $this->escape($tool['color']); ?> flex-shrink-0 mt-1" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <div>
                                        <h5 class="card-title mb-1"><?php echo $this->escape($tool['name']); ?></h5>
                                        <p class="card-text text-muted mb-2"><?php echo $this->escape($tool['description']); ?></p>
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
        <?php endforeach; ?>
    <?php endif; ?>
</div>
