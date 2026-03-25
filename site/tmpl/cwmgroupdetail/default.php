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
use Joomla\CMS\Uri\Uri;

/** @var \CWM\Component\Livingword\Site\View\Cwmgroupdetail\HtmlView $this */

$group      = $this->group;
$members    = $this->members;
$userRole   = $this->userRole;
$canView    = $this->canViewProgress;
$isMember   = $userRole !== '';
$currentUid = (int) (\Joomla\CMS\Factory::getApplication()->getIdentity()->id ?? 0);
?>
<div class="com-livingword-groupdetail">
    <?php echo $this->menu; ?>

    <?php if (!$group) : ?>
        <div class="alert alert-warning mt-3"><?php echo Text::_('COM_LIVINGWORD_GROUP_NOT_FOUND'); ?></div>
    <?php else : ?>
        <div class="mt-3">
            <h2><?php echo $this->escape($group->name); ?></h2>

            <?php if (!empty($group->description)) : ?>
                <p class="text-muted"><?php echo $this->escape($group->description); ?></p>
            <?php endif; ?>

            <p>
                <strong><?php echo Text::_('COM_LIVINGWORD_GROUP_PLAN'); ?>:</strong>
                <?php echo $this->escape($group->plan_title); ?>
                &middot;
                <strong><?php echo Text::_('COM_LIVINGWORD_GROUP_START_DATE'); ?>:</strong>
                <?php echo $this->escape($group->start_date); ?>
            </p>

            <?php // Join / Leave buttons ?>
            <?php if (!$isMember) : ?>
                <form method="post" action="<?php echo Route::_('index.php?option=com_livingword&task=cwmgroup.join'); ?>" class="mb-3">
                    <input type="hidden" name="group_id" value="<?php echo (int) $group->id; ?>">
                    <?php echo HTMLHelper::_('form.token'); ?>
                    <button type="submit" class="btn btn-primary">
                        <?php echo Text::_('COM_LIVINGWORD_GROUP_JOIN'); ?>
                    </button>
                </form>
            <?php else : ?>
                <form method="post" action="<?php echo Route::_('index.php?option=com_livingword&task=cwmgroup.leave'); ?>" class="mb-3">
                    <input type="hidden" name="group_id" value="<?php echo (int) $group->id; ?>">
                    <?php echo HTMLHelper::_('form.token'); ?>
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <?php echo Text::_('COM_LIVINGWORD_GROUP_LEAVE'); ?>
                    </button>
                </form>
            <?php endif; ?>

            <?php // Member progress table (visible to group admins / component admins) ?>
            <?php if ($canView && !empty($members)) : ?>
                <h3><?php echo Text::_('COM_LIVINGWORD_GROUP_MEMBER_PROGRESS'); ?></h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><?php echo Text::_('COM_LIVINGWORD_GROUP_MEMBER_NAME'); ?></th>
                                <th><?php echo Text::_('COM_LIVINGWORD_GROUP_CURRENT_DAY'); ?></th>
                                <th><?php echo Text::_('COM_LIVINGWORD_GROUP_COMPLETION'); ?></th>
                                <th><?php echo Text::_('COM_LIVINGWORD_GROUP_STREAK'); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member) : ?>
                                <?php $rowClass = $member->progress_percent >= 80 ? 'table-success' : ''; ?>
                                <tr class="<?php echo $rowClass; ?>">
                                    <td><?php echo $this->escape($member->name); ?></td>
                                    <td><?php echo Text::sprintf('COM_LIVINGWORD_DAY_OF', $member->current_day, $member->total_days); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-success" style="width: <?php echo $member->progress_percent; ?>%"></div>
                                            </div>
                                            <small><?php echo $member->progress_percent; ?>%</small>
                                        </div>
                                    </td>
                                    <td><?php echo (int) $member->streak_current; ?></td>
                                    <td>
                                        <?php if ((int) $member->user_id !== $currentUid) : ?>
                                            <form method="post" action="<?php echo Route::_('index.php?option=com_livingword&task=cwmgroup.removemember'); ?>" class="d-inline">
                                                <input type="hidden" name="group_id" value="<?php echo (int) $group->id; ?>">
                                                <input type="hidden" name="user_id" value="<?php echo (int) $member->user_id; ?>">
                                                <?php echo HTMLHelper::_('form.token'); ?>
                                                <button type="submit" class="btn btn-outline-danger btn-sm"
                                                        onclick="return confirm('<?php echo $this->escape(Text::_('COM_LIVINGWORD_GROUP_REMOVE_CONFIRM')); ?>')">
                                                    <?php echo Text::_('COM_LIVINGWORD_GROUP_REMOVE'); ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php // Invite link for group admins ?>
            <?php if ($userRole === 'group_admin' && !empty($group->invite_token)) : ?>
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo Text::_('COM_LIVINGWORD_GROUP_INVITE_LINK'); ?></h5>
                        <p class="card-text text-muted"><?php echo Text::_('COM_LIVINGWORD_GROUP_INVITE_DESC'); ?></p>
                        <?php
                        $inviteUrl = Uri::root() . 'index.php?option=com_livingword&task=cwmgroup.join&token='
                            . urlencode($group->invite_token);
                        ?>
                        <div class="input-group">
                            <input type="text" class="form-control" readonly value="<?php echo $this->escape($inviteUrl); ?>">
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="navigator.clipboard.writeText(this.previousElementSibling.value)">
                                <?php echo Text::_('COM_LIVINGWORD_GROUP_COPY_LINK'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
