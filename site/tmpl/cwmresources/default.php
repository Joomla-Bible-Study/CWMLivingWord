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

$this->getDocument()->getWebAssetManager()
    ->registerAndUseStyle('com_livingword.main', 'media/com_livingword/css/livingword.css');
?>
<div class="com-livingword-resources">
    <?php echo $this->menu; ?>

    <div class="livingword-plan-header">
        <h2><?php echo Text::_('COM_LIVINGWORD_RESOURCES'); ?></h2>
    </div>

    <?php if (empty($this->resources)) : ?>
        <div class="alert alert-info"><?php echo Text::_('COM_LIVINGWORD_NO_RESOURCES'); ?></div>
    <?php else : ?>
        <?php foreach ($this->resources as $category => $links) : ?>
            <h3 class="mt-4 mb-3">
                <span class="icon-folder text-primary" aria-hidden="true"></span>
                <?php echo $this->escape($category); ?>
            </h3>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-3">
                <?php foreach ($links as $link) : ?>
                    <div class="col">
                        <a href="<?php echo $this->escape($link->url); ?>"
                           <?php echo $link->target == 2 ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
                           class="card h-100 text-decoration-none livingword-resource-card">
                            <div class="card-body d-flex align-items-center gap-3">
                                <span class="icon-link fa-lg text-primary flex-shrink-0" aria-hidden="true"></span>
                                <div>
                                    <h5 class="card-title mb-0"><?php echo $this->escape($link->name); ?></h5>
                                    <small class="text-muted"><?php echo $this->escape(parse_url($link->url, PHP_URL_HOST)); ?></small>
                                    <?php if (!empty($link->tags)) : ?>
                                        <div class="livingword-resource-tags mt-1">
                                            <?php foreach ($link->tags as $tag) : ?>
                                                <span class="badge bg-light text-dark border me-1"><?php echo $this->escape($tag->title); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($link->target == 2) : ?>
                                    <span class="icon-out-2 text-muted ms-auto flex-shrink-0" aria-hidden="true"></span>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
