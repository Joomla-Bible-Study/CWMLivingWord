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

/** @var \CWM\Component\Livingword\Administrator\View\Cwmtool\HtmlView $this */

$this->getDocument()->getWebAssetManager()->useScript('form.validate');
?>
<form action="<?php echo Route::_('index.php?option=com_livingword&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="main-card p-3">
        <div class="row">
            <div class="col-lg-9">
                <?php echo $this->form->renderField('name'); ?>
                <?php echo $this->form->renderField('description'); ?>
                <?php echo $this->form->renderField('url'); ?>
                <fieldset class="border rounded p-3 mb-3">
                    <legend class="fs-6 fw-semibold w-auto px-2 mb-0"><?php echo Text::_('COM_LIVINGWORD_TOOL_APPEARANCE'); ?></legend>
                    <div class="row">
                        <div class="col-md-4">
                            <?php echo $this->form->renderField('icon'); ?>
                        </div>
                        <div class="col-md-4">
                            <?php echo $this->form->renderField('color'); ?>
                        </div>
                        <div class="col-md-4 d-flex align-items-center justify-content-center">
                            <div id="iconPreview" style="font-size: 3.5rem; min-height: 4rem; line-height: 4rem;">
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
            <div class="col-lg-3">
                <?php echo $this->form->renderField('published'); ?>
                <?php echo $this->form->renderField('catid'); ?>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const iconSelect = document.getElementById('jform_icon');
    const colorSelect = document.getElementById('jform_color');
    const preview = document.getElementById('iconPreview');

    const colorMap = {
        '': '',
        'text-primary': '#0d6efd',
        'text-success': '#198754',
        'text-danger': '#dc3545',
        'text-warning': '#ffc107',
        'text-info': '#0dcaf0',
        'text-secondary': '#6c757d',
        'text-dark': '#212529'
    };

    function updatePreview() {
        const icon = iconSelect ? iconSelect.value : '';
        const color = colorSelect ? colorSelect.value : '';
        const hex = colorMap[color] || '';
        if (icon) {
            preview.innerHTML = '<span class="' + icon + '"' + (hex ? ' style="color:' + hex + '"' : '') + '></span>';
        } else {
            preview.innerHTML = '<span class="text-muted" style="font-size:1rem;"><?php echo Text::_('COM_LIVINGWORD_TOOL_NO_ICON', true); ?></span>';
        }
    }

    if (iconSelect) iconSelect.addEventListener('change', updatePreview);
    if (colorSelect) colorSelect.addEventListener('change', updatePreview);

    // Initial preview with color
    updatePreview();

    // Add "Test" button next to URL input
    const urlField = document.getElementById('jform_url');
    if (urlField) {
        const wrapper = document.createElement('div');
        wrapper.className = 'input-group';
        urlField.parentNode.insertBefore(wrapper, urlField);
        wrapper.appendChild(urlField);

        const btn = document.createElement('a');
        btn.className = 'btn btn-outline-secondary';
        btn.target = '_blank';
        btn.rel = 'noopener noreferrer';
        btn.textContent = 'Test';
        btn.href = urlField.value || '#';
        if (!urlField.value) btn.classList.add('disabled');
        wrapper.appendChild(btn);

        urlField.addEventListener('input', function() {
            btn.href = urlField.value || '#';
            btn.classList.toggle('disabled', !urlField.value);
        });
    }
});
</script>
