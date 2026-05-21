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
use Joomla\CMS\Session\Session;

/** @var \CWM\Component\Livingword\Site\View\Cwminvite\HtmlView $this */

$wa = $this->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('com_livingword.main', 'media/com_livingword/css/livingword.css');
?>
<div class="com-livingword-invite">
    <?php if (!$this->group) : ?>
        <div class="alert alert-warning" role="alert">
            <h2 class="alert-heading h4"><?php echo Text::_('COM_LIVINGWORD_INVITE_NOT_FOUND_TITLE'); ?></h2>
            <p class="mb-0"><?php echo Text::_('COM_LIVINGWORD_INVITE_NOT_FOUND_BODY'); ?></p>
        </div>
    <?php else : ?>
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <p class="text-muted text-uppercase small mb-2">
                    <?php echo Text::_('COM_LIVINGWORD_INVITE_LABEL'); ?>
                </p>
                <h1 class="card-title h2 mb-3">
                    <?php echo $this->escape($this->group->name); ?>
                </h1>

                <?php if ($this->group->leader_name !== '') : ?>
                    <p class="text-muted mb-3">
                        <?php echo Text::sprintf(
                            'COM_LIVINGWORD_INVITE_FROM',
                            $this->escape($this->group->leader_name)
                        ); ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($this->group->description)) : ?>
                    <p class="lead mb-3">
                        <?php echo nl2br($this->escape($this->group->description)); ?>
                    </p>
                <?php endif; ?>

                <ul class="list-unstyled mb-4">
                    <?php if (!empty($this->group->plan_title)) : ?>
                        <li>
                            <strong><?php echo Text::_('COM_LIVINGWORD_INVITE_READING_PLAN'); ?>:</strong>
                            <?php echo $this->escape($this->group->plan_title); ?>
                            <?php if ((int) $this->group->total_days > 0) : ?>
                                <span class="text-muted">
                                    (<?php echo Text::sprintf('COM_LIVINGWORD_INVITE_DAYS', (int) $this->group->total_days); ?>)
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($this->group->start_date) && $this->group->start_date !== '0000-00-00') : ?>
                        <li>
                            <strong><?php echo Text::_('COM_LIVINGWORD_INVITE_START_DATE'); ?>:</strong>
                            <?php echo HTMLHelper::_('date', $this->group->start_date, Text::_('DATE_FORMAT_LC3')); ?>
                        </li>
                    <?php endif; ?>
                    <li>
                        <strong><?php echo Text::_('COM_LIVINGWORD_INVITE_MEMBERS'); ?>:</strong>
                        <?php echo Text::plural(
                            'COM_LIVINGWORD_INVITE_MEMBER_COUNT',
                            (int) $this->group->member_count
                        ); ?>
                    </li>
                </ul>

                <hr>

                <?php if ($this->alreadyMember) : ?>
                    <p><?php echo Text::_('COM_LIVINGWORD_INVITE_ALREADY_MEMBER'); ?></p>
                    <a href="<?php echo Route::_(
                        'index.php?option=com_livingword&view=cwmgroupdetail&group_id=' . (int) $this->group->id
                    ); ?>" class="btn btn-primary btn-lg">
                        <?php echo Text::_('COM_LIVINGWORD_INVITE_OPEN_GROUP'); ?>
                    </a>
                <?php elseif (!$this->isGuest) : ?>
                    <p><?php echo Text::_('COM_LIVINGWORD_INVITE_AUTHED_INTRO'); ?></p>
                    <form action="<?php echo Route::_('index.php?option=com_livingword&task=cwmgroup.join'); ?>"
                          method="post" class="d-inline">
                        <input type="hidden" name="token" value="<?php echo $this->escape($this->token); ?>">
                        <?php echo HTMLHelper::_('form.token'); ?>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <?php echo Text::_('COM_LIVINGWORD_INVITE_JOIN_BUTTON'); ?>
                        </button>
                    </form>
                <?php else : ?>
                    <p><?php echo Text::_('COM_LIVINGWORD_INVITE_GUEST_INTRO'); ?></p>
                    <div class="d-grid gap-2 d-sm-flex">
                        <?php if ($this->registrationAllowed) : ?>
                            <a href="<?php echo $this->escape($this->registerUrl); ?>"
                               class="btn btn-primary btn-lg">
                                <?php echo Text::_('COM_LIVINGWORD_INVITE_REGISTER_BUTTON'); ?>
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo $this->escape($this->loginUrl); ?>"
                           class="btn btn-outline-primary btn-lg">
                            <?php echo Text::_('COM_LIVINGWORD_INVITE_LOGIN_BUTTON'); ?>
                        </a>
                    </div>
                    <?php if (!$this->registrationAllowed) : ?>
                        <p class="text-muted small mt-3 mb-0">
                            <?php echo Text::_('COM_LIVINGWORD_INVITE_REGISTRATION_DISABLED'); ?>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>