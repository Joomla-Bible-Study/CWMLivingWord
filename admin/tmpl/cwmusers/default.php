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
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/** @var \CWM\Component\Livingword\Administrator\View\Cwmusers\HtmlView $this */

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_livingword&view=cwmusers'); ?>" method="post" name="adminForm" id="adminForm">
    <div class="row">
        <div class="col-md-12">
            <div id="j-main-container" class="j-main-container">
                <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
                <?php if (empty($this->items)) : ?>
                    <div class="alert alert-info">
                        <span class="icon-info-circle" aria-hidden="true"></span>
                        <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                    </div>
                <?php else : ?>
                    <table class="table itemList" id="userList">
                        <caption class="visually-hidden"><?php echo Text::_('COM_LIVINGWORD_MANAGE_SUBSCRIBERS'); ?></caption>
                        <thead>
                            <tr>
                                <td class="w-1 text-center">
                                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                                </td>
                                <th scope="col">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_LIVINGWORD_USER_NAME', 'u.name', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col">
                                    <?php echo Text::_('COM_LIVINGWORD_USER_PLAN'); ?>
                                </th>
                                <th scope="col" class="w-15 text-center">
                                    <?php echo Text::_('COM_LIVINGWORD_USER_PROGRESS'); ?>
                                </th>
                                <th scope="col" class="w-5 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_LIVINGWORD_USER_STREAK', 'a.streak_current', $listDirn, $listOrder); ?>
                                </th>
                                <th scope="col" class="w-5 text-center">
                                    <?php echo Text::_('COM_LIVINGWORD_USER_EMAIL_SUB'); ?>
                                </th>
                                <th scope="col" class="w-5 text-center d-none d-md-table-cell">
                                    <?php echo Text::_('COM_LIVINGWORD_USER_VERSION'); ?>
                                </th>
                                <th scope="col" class="w-10 d-none d-md-table-cell">
                                    <?php echo Text::_('COM_LIVINGWORD_USER_STARTDATE'); ?>
                                </th>
                                <th scope="col" class="w-5 d-none d-lg-table-cell text-center">
                                    <?php echo Text::_('COM_LIVINGWORD_USER_LAST_ACTIVE'); ?>
                                </th>
                                <th scope="col" class="w-3 text-center">
                                    <?php echo HTMLHelper::_('searchtools.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->items as $i => $item) :
                                $totalDays      = (int) ($item->total_days ?? 0);
                                $completedCount = (int) ($item->completed_count ?? 0);
                                $progressPct    = ($totalDays > 0) ? round(($completedCount / $totalDays) * 100) : 0;
                                $streak         = (int) ($item->streak_current ?? 0);
                                $streakBest     = (int) ($item->streak_best ?? 0);
                            ?>
                                <tr class="row<?php echo $i % 2; ?>">
                                    <td class="text-center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->username ?? ''); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo $this->escape($item->username ?? Text::_('COM_LIVINGWORD_USER_UNKNOWN')); ?></strong>
                                        <?php if (!empty($item->user_email)) : ?>
                                            <br><small class="text-muted"><?php echo $this->escape($item->user_email); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $this->escape($item->plan_title ?? '—'); ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($totalDays > 0) : ?>
                                            <div class="progress" style="height:6px;min-width:80px;" title="<?php echo $completedCount . ' / ' . $totalDays; ?>">
                                                <div class="progress-bar <?php echo $progressPct >= 100 ? 'bg-success' : 'bg-primary'; ?>" style="width:<?php echo $progressPct; ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?php echo $completedCount; ?>/<?php echo $totalDays; ?> (<?php echo $progressPct; ?>%)</small>
                                        <?php else : ?>
                                            <small class="text-muted">—</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($streak > 0) : ?>
                                            <span class="badge bg-warning text-dark" title="<?php echo Text::sprintf('COM_LIVINGWORD_STREAK_BEST', $streakBest); ?>">
                                                <?php echo $streak; ?>
                                            </span>
                                        <?php else : ?>
                                            <small class="text-muted">0</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($item->email) : ?>
                                            <span class="icon-publish text-success" aria-hidden="true"></span>
                                            <span class="visually-hidden"><?php echo Text::_('COM_LIVINGWORD_FILTER_SUBSCRIBED'); ?></span>
                                        <?php else : ?>
                                            <span class="icon-unpublish text-muted" aria-hidden="true"></span>
                                            <span class="visually-hidden"><?php echo Text::_('COM_LIVINGWORD_FILTER_UNSUBSCRIBED'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center d-none d-md-table-cell">
                                        <small><?php echo $this->escape($item->bible_version ?: '—'); ?></small>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <small><?php echo $this->escape($item->start_date ?: '—'); ?></small>
                                    </td>
                                    <td class="text-center d-none d-lg-table-cell">
                                        <?php if (!empty($item->streak_last_date)) : ?>
                                            <small class="text-muted"><?php echo HTMLHelper::_('date', $item->streak_last_date, Text::_('DATE_FORMAT_LC4')); ?></small>
                                        <?php else : ?>
                                            <small class="text-muted">—</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo (int) $item->id; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php echo $this->pagination->getListFooter(); ?>
                <?php endif; ?>

                <input type="hidden" name="task" value="">
                <input type="hidden" name="boxchecked" value="0">
                <?php echo HTMLHelper::_('form.token'); ?>
            </div>
        </div>
    </div>
</form>
