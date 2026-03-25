<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var \CWM\Component\Livingword\Administrator\View\Cwmutilities\HtmlView $this */

$token = Session::getFormToken();
?>
<div class="row">
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><?php echo Text::_('COM_LIVINGWORD_DATABASE_MAINTENANCE'); ?></h3>
            </div>
            <div class="card-body">
                <p><?php echo Text::_('COM_LIVINGWORD_DATABASE_MAINTENANCE_DESC'); ?></p>
                <div class="btn-group" role="group">
                    <a href="<?php echo Route::_('index.php?option=com_livingword&task=cwmutilities.optimize&' . $token . '=1'); ?>" class="btn btn-primary">
                        <span class="icon-refresh" aria-hidden="true"></span>
                        <?php echo Text::_('COM_LIVINGWORD_OPTIMIZE'); ?>
                    </a>
                    <a href="<?php echo Route::_('index.php?option=com_livingword&task=cwmutilities.check&' . $token . '=1'); ?>" class="btn btn-info">
                        <span class="icon-search" aria-hidden="true"></span>
                        <?php echo Text::_('COM_LIVINGWORD_CHECK'); ?>
                    </a>
                    <a href="<?php echo Route::_('index.php?option=com_livingword&task=cwmutilities.repair&' . $token . '=1'); ?>" class="btn btn-warning">
                        <span class="icon-wrench" aria-hidden="true"></span>
                        <?php echo Text::_('COM_LIVINGWORD_REPAIR'); ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><?php echo Text::_('COM_LIVINGWORD_BACKUP'); ?></h3>
            </div>
            <div class="card-body">
                <p><?php echo Text::_('COM_LIVINGWORD_BACKUP_DESC'); ?></p>
                <a href="<?php echo Route::_('index.php?option=com_livingword&task=cwmutilities.backup&' . $token . '=1'); ?>" class="btn btn-success">
                    <span class="icon-download" aria-hidden="true"></span>
                    <?php echo Text::_('COM_LIVINGWORD_BACKUP_DOWNLOAD'); ?>
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><?php echo Text::_('COM_LIVINGWORD_CSV_IMPORT'); ?></h3>
            </div>
            <div class="card-body">
                <p><?php echo Text::_('COM_LIVINGWORD_CSV_IMPORT_DESC'); ?></p>
                <form action="<?php echo Route::_('index.php?option=com_livingword&task=cwmutilities.csvimport'); ?>"
                      method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="plan_id" class="form-label"><?php echo Text::_('COM_LIVINGWORD_CSV_SELECT_PLAN'); ?></label>
                        <select name="plan_id" id="csv_plan_id" class="form-select" required>
                            <option value=""><?php echo Text::_('COM_LIVINGWORD_SELECT_PLAN'); ?></option>
                            <?php
                            $db    = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
                            $query = $db->getQuery(true)
                                ->select($db->quoteName(['id', 'title']))
                                ->from($db->quoteName('#__livingword_plans'))
                                ->where($db->quoteName('published') . ' = 1')
                                ->order($db->quoteName('ordering') . ' ASC');
                            $db->setQuery($query);
                            $plans = $db->loadObjectList() ?: [];

                            foreach ($plans as $csvPlan) : ?>
                                <option value="<?php echo (int) $csvPlan->id; ?>">
                                    <?php echo htmlspecialchars($csvPlan->title, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="csv_file" class="form-label"><?php echo Text::_('COM_LIVINGWORD_CSV_FILE'); ?></label>
                        <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv,.txt" required>
                        <small class="text-muted"><?php echo Text::_('COM_LIVINGWORD_CSV_FORMAT'); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo Text::_('COM_LIVINGWORD_CSV_MODE'); ?></label>
                        <div class="form-check">
                            <input type="radio" name="import_mode" id="mode_append" value="append" class="form-check-input" checked>
                            <label for="mode_append" class="form-check-label"><?php echo Text::_('COM_LIVINGWORD_CSV_MODE_APPEND'); ?></label>
                        </div>
                        <div class="form-check">
                            <input type="radio" name="import_mode" id="mode_replace" value="replace" class="form-check-input">
                            <label for="mode_replace" class="form-check-label"><?php echo Text::_('COM_LIVINGWORD_CSV_MODE_REPLACE'); ?></label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <span class="icon-upload" aria-hidden="true"></span>
                        <?php echo Text::_('COM_LIVINGWORD_CSV_IMPORT'); ?>
                    </button>
                    <?php echo \Joomla\CMS\HTML\HTMLHelper::_('form.token'); ?>
                </form>
            </div>
        </div>
    </div>
</div>
