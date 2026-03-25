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
use Joomla\CMS\Session\Session;

/** @var \CWM\Component\Livingword\Site\View\Cwmgroups\HtmlView $this */
?>
<div class="com-livingword-groups">
    <?php echo $this->menu; ?>

    <h2 class="mt-3"><?php echo Text::_('COM_LIVINGWORD_MY_GROUPS'); ?></h2>

    <?php if (empty($this->myGroups)) : ?>
        <div class="alert alert-info"><?php echo Text::_('COM_LIVINGWORD_NO_GROUPS_JOINED'); ?></div>
    <?php else : ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-4">
            <?php foreach ($this->myGroups as $group) : ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="<?php echo Route::_('index.php?option=com_livingword&view=cwmgroupdetail&group_id=' . (int) $group->id); ?>">
                                    <?php echo $this->escape($group->name); ?>
                                </a>
                            </h5>
                            <p class="card-text text-muted mb-1">
                                <?php echo $this->escape($group->plan_title); ?>
                            </p>
                            <p class="card-text mb-1">
                                <span class="badge bg-<?php echo $group->role === 'group_admin' ? 'warning' : 'secondary'; ?>">
                                    <?php echo $this->escape($group->role === 'group_admin' ? Text::_('COM_LIVINGWORD_ROLE_ADMIN') : Text::_('COM_LIVINGWORD_ROLE_MEMBER')); ?>
                                </span>
                            </p>
                            <p class="card-text">
                                <small class="text-muted">
                                    <?php echo Text::sprintf('COM_LIVINGWORD_GROUP_MEMBERS_COUNT', (int) $group->member_count); ?>
                                    &middot;
                                    <?php echo Text::sprintf('COM_LIVINGWORD_GROUP_STARTED', $this->escape($group->start_date)); ?>
                                </small>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2><?php echo Text::_('COM_LIVINGWORD_AVAILABLE_GROUPS'); ?></h2>

    <?php if (empty($this->joinableGroups)) : ?>
        <div class="alert alert-info"><?php echo Text::_('COM_LIVINGWORD_NO_GROUPS_AVAILABLE'); ?></div>
    <?php else : ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
            <?php foreach ($this->joinableGroups as $group) : ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $this->escape($group->name); ?></h5>
                            <p class="card-text text-muted mb-1">
                                <?php echo $this->escape($group->plan_title); ?>
                            </p>
                            <p class="card-text mb-2">
                                <small class="text-muted">
                                    <?php echo Text::sprintf('COM_LIVINGWORD_GROUP_MEMBERS_COUNT', (int) $group->member_count); ?>
                                    &middot;
                                    <?php echo Text::sprintf('COM_LIVINGWORD_GROUP_STARTED', $this->escape($group->start_date)); ?>
                                </small>
                            </p>
                            <form method="post" action="<?php echo Route::_('index.php?option=com_livingword&task=cwmgroup.join'); ?>">
                                <input type="hidden" name="group_id" value="<?php echo (int) $group->id; ?>">
                                <?php echo \Joomla\CMS\HTML\HTMLHelper::_('form.token'); ?>
                                <button type="submit" class="btn btn-primary btn-sm">
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
