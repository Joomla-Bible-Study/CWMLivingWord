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
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Livingword\Site\View\Cwmgroups\HtmlView $this */

$this->getDocument()->getWebAssetManager()
    ->registerAndUseStyle('com_livingword.main', 'media/com_livingword/css/livingword.css');
?>
<div class="com-livingword-groups">
    <?php echo $this->menu; ?>

    <?php // ── My Groups ── ?>
    <div class="livingword-plan-header">
        <h2><?php echo Text::_('COM_LIVINGWORD_MY_GROUPS'); ?></h2>
    </div>

    <?php if (empty($this->myGroups)) : ?>
        <div class="alert alert-info"><?php echo Text::_('COM_LIVINGWORD_NO_GROUPS_JOINED'); ?></div>
    <?php else : ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-4">
            <?php foreach ($this->myGroups as $group) : ?>
                <div class="col">
                    <a href="<?php echo Route::_('index.php?option=com_livingword&view=cwmgroupdetail&group_id=' . (int) $group->id); ?>"
                       class="card h-100 text-decoration-none livingword-resource-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?php echo $this->escape($group->name); ?></h5>
                                <span class="badge bg-<?php echo $group->role === 'group_admin' ? 'warning' : 'secondary'; ?>">
                                    <?php echo $this->escape($group->role === 'group_admin' ? Text::_('COM_LIVINGWORD_ROLE_ADMIN') : Text::_('COM_LIVINGWORD_ROLE_MEMBER')); ?>
                                </span>
                            </div>
                            <p class="text-muted small mb-2"><?php echo $this->escape($group->plan_title); ?></p>
                            <div class="d-flex gap-3 text-muted small">
                                <span>
                                    <span class="icon-users" aria-hidden="true"></span>
                                    <?php echo Text::sprintf('COM_LIVINGWORD_GROUP_MEMBERS_COUNT', (int) $group->member_count); ?>
                                </span>
                                <span>
                                    <span class="icon-calendar" aria-hidden="true"></span>
                                    <?php echo $this->escape($group->start_date); ?>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php // ── Available Groups ── ?>
    <div class="livingword-plan-header">
        <h2><?php echo Text::_('COM_LIVINGWORD_AVAILABLE_GROUPS'); ?></h2>
    </div>

    <?php if (empty($this->joinableGroups)) : ?>
        <div class="alert alert-info"><?php echo Text::_('COM_LIVINGWORD_NO_GROUPS_AVAILABLE'); ?></div>
    <?php else : ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
            <?php foreach ($this->joinableGroups as $group) : ?>
                <div class="col">
                    <div class="card h-100 livingword-resource-card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $this->escape($group->name); ?></h5>
                            <p class="text-muted small mb-2"><?php echo $this->escape($group->plan_title); ?></p>
                            <div class="d-flex gap-3 text-muted small mb-3">
                                <span>
                                    <span class="icon-users" aria-hidden="true"></span>
                                    <?php echo Text::sprintf('COM_LIVINGWORD_GROUP_MEMBERS_COUNT', (int) $group->member_count); ?>
                                </span>
                                <span>
                                    <span class="icon-calendar" aria-hidden="true"></span>
                                    <?php echo $this->escape($group->start_date); ?>
                                </span>
                            </div>
                            <form method="post" action="<?php echo Route::_('index.php?option=com_livingword&task=cwmgroup.join'); ?>">
                                <input type="hidden" name="group_id" value="<?php echo (int) $group->id; ?>">
                                <?php echo \Joomla\CMS\HTML\HTMLHelper::_('form.token'); ?>
                                <button type="submit" class="btn btn-primary btn-sm livingword-mark-read-btn">
                                    <span class="icon-plus" aria-hidden="true"></span>
                                    <?php echo Text::_('COM_LIVINGWORD_GROUP_JOIN'); ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
