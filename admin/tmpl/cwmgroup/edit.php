<?php

/**
 * @package    Livingword.Admin
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

/** @var \CWM\Component\Livingword\Administrator\View\Cwmgroup\HtmlView $this */

$isNew = ((int) $this->item->id === 0);
?>
<form action="<?php echo Route::_('index.php?option=com_livingword&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'groupTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php // --- Details Tab --- ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'groupTab', 'details', Text::_('COM_LIVINGWORD_TAB_DETAILS')); ?>
        <div class="row">
            <div class="col-lg-9">
                <?php echo $this->form->renderField('name'); ?>
                <?php echo $this->form->renderField('description'); ?>
                <?php echo $this->form->renderField('plan_id'); ?>
                <?php echo $this->form->renderField('start_date'); ?>
                <?php echo $this->form->renderField('invite_token'); ?>
                <?php if (!$isNew && !empty($this->item->invite_token)) : ?>
                    <div class="control-group">
                        <div class="control-label">
                            <label><?php echo Text::_('COM_LIVINGWORD_GROUP_INVITE_URL'); ?></label>
                        </div>
                        <div class="controls">
                            <code><?php echo Uri::root() . 'index.php?option=com_livingword&task=cwmgroup.join&token=' . $this->escape($this->item->invite_token); ?></code>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-3">
                <?php echo $this->form->renderField('join_mode'); ?>
                <?php echo $this->form->renderField('published'); ?>
                <?php echo $this->form->renderField('ordering'); ?>
                <?php echo $this->form->renderField('id'); ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php // --- Members Tab --- ?>
        <?php
        $membersCount = \count($this->members);
        $membersLabel = Text::_('COM_LIVINGWORD_TAB_MEMBERS');
        if ($membersCount > 0) {
            $membersLabel .= ' <span class="badge bg-info">' . $membersCount . '</span>';
        }
        ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'groupTab', 'members', $membersLabel); ?>
        <div class="row">
            <div class="col-12">
                <?php if ($isNew) : ?>
                    <div class="alert alert-info">
                        <?php echo Text::_('COM_LIVINGWORD_SAVE_GROUP_FIRST'); ?>
                    </div>
                <?php elseif (empty($this->members)) : ?>
                    <div class="alert alert-info">
                        <?php echo Text::_('COM_LIVINGWORD_GROUP_NO_MEMBERS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col"><?php echo Text::_('COM_LIVINGWORD_GROUP_MEMBER_NAME'); ?></th>
                                <th scope="col"><?php echo Text::_('COM_LIVINGWORD_GROUP_MEMBER_ROLE'); ?></th>
                                <th scope="col"><?php echo Text::_('COM_LIVINGWORD_GROUP_MEMBER_JOINED'); ?></th>
                                <th scope="col" class="w-10 text-center"><?php echo Text::_('COM_LIVINGWORD_GROUP_MEMBER_REMOVE'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->members as $member) : ?>
                                <tr>
                                    <td><?php echo $this->escape($member->user_name ?? Text::_('COM_LIVINGWORD_UNKNOWN_USER')); ?></td>
                                    <td>
                                        <select class="form-select form-select-sm" style="width:auto;" onchange="window.location.href='<?php echo Route::_('index.php?option=com_livingword&task=cwmgroup.updateMemberRole&member_id=' . (int) $member->id . '&id=' . (int) $this->item->id . '&' . \Joomla\CMS\Session\Session::getFormToken() . '=1&role=', false); ?>'+this.value">
                                            <option value="member" <?php echo ($member->role === 'member') ? 'selected' : ''; ?>><?php echo Text::_('COM_LIVINGWORD_GROUP_ROLE_MEMBER'); ?></option>
                                            <option value="leader" <?php echo ($member->role === 'leader') ? 'selected' : ''; ?>><?php echo Text::_('COM_LIVINGWORD_GROUP_ROLE_LEADER'); ?></option>
                                        </select>
                                    </td>
                                    <td><?php echo $this->escape($member->joined_at ?? ''); ?></td>
                                    <td class="text-center">
                                        <a href="<?php echo Route::_('index.php?option=com_livingword&task=cwmgroup.removeMember&member_id=' . (int) $member->id . '&id=' . (int) $this->item->id . '&' . \Joomla\CMS\Session\Session::getFormToken() . '=1'); ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?php echo Text::_('COM_LIVINGWORD_CONFIRM_REMOVE_MEMBER'); ?>');">
                                            <span class="icon-times" aria-hidden="true"></span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
