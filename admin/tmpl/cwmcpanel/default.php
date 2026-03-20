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

$counts = $this->counts;
?>
<div class="row">
    <div class="col-lg-9">
        <div class="row">
            <div class="col-md-4">
                <div class="card text-center mb-3">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo $counts['plans'] ?? 0; ?></h3>
                        <p class="card-text"><?php echo Text::_('COM_LIVINGWORD_MANAGE_PLANS'); ?></p>
                        <a href="<?php echo Route::_('index.php?option=com_livingword&view=cwmplans'); ?>" class="btn btn-primary">
                            <?php echo Text::_('COM_LIVINGWORD_MANAGE_PLANS'); ?>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center mb-3">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo $counts['links'] ?? 0; ?></h3>
                        <p class="card-text"><?php echo Text::_('COM_LIVINGWORD_MANAGE_LINKS'); ?></p>
                        <a href="<?php echo Route::_('index.php?option=com_livingword&view=cwmlinks'); ?>" class="btn btn-primary">
                            <?php echo Text::_('COM_LIVINGWORD_MANAGE_LINKS'); ?>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center mb-3">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo $counts['users'] ?? 0; ?></h3>
                        <p class="card-text"><?php echo Text::_('COM_LIVINGWORD_MANAGE_SUBSCRIBERS'); ?></p>
                        <a href="<?php echo Route::_('index.php?option=com_livingword&view=cwmusers'); ?>" class="btn btn-primary">
                            <?php echo Text::_('COM_LIVINGWORD_MANAGE_SUBSCRIBERS'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-4">
                <a href="<?php echo Route::_('index.php?option=com_livingword&view=cwmutilities'); ?>" class="btn btn-outline-secondary w-100">
                    <span class="icon-wrench" aria-hidden="true"></span>
                    <?php echo Text::_('COM_LIVINGWORD_UTILITIES'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
