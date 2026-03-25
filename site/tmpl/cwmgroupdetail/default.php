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

$this->getDocument()->getWebAssetManager()
    ->registerAndUseStyle('com_livingword.main', 'media/com_livingword/css/livingword.css');
?>
<div class="com-livingword-groupdetail">
    <?php echo $this->menu; ?>

    <?php if (!$group) : ?>
        <div class="alert alert-warning mt-3"><?php echo Text::_('COM_LIVINGWORD_GROUP_NOT_FOUND'); ?></div>
    <?php else : ?>

        <?php // ── Group Header Card ── ?>
        <div class="card livingword-reading-card mt-3 mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h2 class="mb-0"><?php echo $this->escape($group->name); ?></h2>
                    <?php if ($isMember) : ?>
                        <span class="badge bg-<?php echo $userRole === 'group_admin' ? 'warning' : 'secondary'; ?> fs-6">
                            <?php echo $userRole === 'group_admin' ? Text::_('COM_LIVINGWORD_ROLE_ADMIN') : Text::_('COM_LIVINGWORD_ROLE_MEMBER'); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($group->description)) : ?>
                    <p class="text-muted mb-3"><?php echo $this->escape($group->description); ?></p>
                <?php endif; ?>

                <div class="d-flex gap-4 text-muted small mb-3">
                    <span>
                        <span class="icon-book" aria-hidden="true"></span>
                        <strong><?php echo Text::_('COM_LIVINGWORD_GROUP_PLAN'); ?>:</strong>
                        <?php echo $this->escape($group->plan_title); ?>
                    </span>
                    <span>
                        <span class="icon-calendar" aria-hidden="true"></span>
                        <strong><?php echo Text::_('COM_LIVINGWORD_GROUP_START_DATE'); ?>:</strong>
                        <?php echo $this->escape($group->start_date); ?>
                    </span>
                </div>

                <?php // Join / Leave ?>
                <?php if (!$isMember) : ?>
                    <form method="post" action="<?php echo Route::_('index.php?option=com_livingword&task=cwmgroup.join'); ?>">
                        <input type="hidden" name="group_id" value="<?php echo (int) $group->id; ?>">
                        <?php echo HTMLHelper::_('form.token'); ?>
                        <button type="submit" class="btn btn-primary livingword-mark-read-btn">
                            <span class="icon-plus" aria-hidden="true"></span>
                            <?php echo Text::_('COM_LIVINGWORD_GROUP_JOIN'); ?>
                        </button>
                    </form>
                <?php else : ?>
                    <form method="post" action="<?php echo Route::_('index.php?option=com_livingword&task=cwmgroup.leave'); ?>">
                        <input type="hidden" name="group_id" value="<?php echo (int) $group->id; ?>">
                        <?php echo HTMLHelper::_('form.token'); ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <?php echo Text::_('COM_LIVINGWORD_GROUP_LEAVE'); ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php // ── Member Progress ── ?>
        <?php if ($canView && !empty($members)) : ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="card-title mb-3">
                        <span class="icon-users" aria-hidden="true"></span>
                        <?php echo Text::_('COM_LIVINGWORD_GROUP_MEMBER_PROGRESS'); ?>
                    </h3>
                    <div class="table-responsive">
                        <table class="table livingword-plan-table mb-0">
                            <thead>
                                <tr>
                                    <th><?php echo Text::_('COM_LIVINGWORD_GROUP_MEMBER_NAME'); ?></th>
                                    <th><?php echo Text::_('COM_LIVINGWORD_GROUP_CURRENT_DAY'); ?></th>
                                    <th><?php echo Text::_('COM_LIVINGWORD_GROUP_COMPLETION'); ?></th>
                                    <th class="text-center"><?php echo Text::_('COM_LIVINGWORD_GROUP_STREAK'); ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $member) : ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $this->escape($member->name); ?></strong>
                                        </td>
                                        <td>
                                            <span class="livingword-day-indicator">
                                                <?php echo Text::sprintf('COM_LIVINGWORD_DAY_OF', $member->current_day, $member->total_days); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px; min-width: 60px;">
                                                    <?php
                                                    $barClass = 'bg-success';

                                                    if ($member->progress_percent < 25) {
                                                        $barClass = 'bg-danger';
                                                    } elseif ($member->progress_percent < 50) {
                                                        $barClass = 'bg-warning';
                                                    } elseif ($member->progress_percent < 75) {
                                                        $barClass = 'bg-info';
                                                    }
                                                    ?>
                                                    <div class="progress-bar <?php echo $barClass; ?>" style="width: <?php echo $member->progress_percent; ?>%"></div>
                                                </div>
                                                <small class="fw-bold"><?php echo $member->progress_percent; ?>%</small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php if ((int) $member->streak_current > 0) : ?>
                                                <span class="badge bg-success rounded-pill"><?php echo (int) $member->streak_current; ?>d</span>
                                            <?php else : ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ((int) $member->user_id !== $currentUid) : ?>
                                                <form method="post" action="<?php echo Route::_('index.php?option=com_livingword&task=cwmgroup.removemember'); ?>" class="d-inline">
                                                    <input type="hidden" name="group_id" value="<?php echo (int) $group->id; ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo (int) $member->user_id; ?>">
                                                    <?php echo HTMLHelper::_('form.token'); ?>
                                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                                            onclick="return confirm('<?php echo $this->escape(Text::_('COM_LIVINGWORD_GROUP_REMOVE_CONFIRM')); ?>')">
                                                        <span class="icon-delete" aria-hidden="true"></span>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php // ── Invite Link ── ?>
        <?php if ($userRole === 'group_admin' && !empty($group->invite_token)) : ?>
            <div class="card livingword-partner-card mb-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <span class="icon-share" aria-hidden="true"></span>
                        <?php echo Text::_('COM_LIVINGWORD_GROUP_INVITE_LINK'); ?>
                    </h5>
                    <p class="text-muted small"><?php echo Text::_('COM_LIVINGWORD_GROUP_INVITE_DESC'); ?></p>
                    <?php
                    $inviteUrl = Uri::root() . 'index.php?option=com_livingword&task=cwmgroup.join&token='
                        . urlencode($group->invite_token);
                    ?>
                    <div class="input-group">
                        <input type="text" class="form-control" readonly value="<?php echo $this->escape($inviteUrl); ?>" id="inviteUrl">
                        <button class="btn btn-outline-primary" type="button"
                                onclick="navigator.clipboard.writeText(document.getElementById('inviteUrl').value); this.textContent='Copied!'">
                            <span class="icon-copy" aria-hidden="true"></span>
                            <?php echo Text::_('COM_LIVINGWORD_GROUP_COPY_LINK'); ?>
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>
