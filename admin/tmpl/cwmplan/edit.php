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

/** @var \CWM\Component\Livingword\Administrator\View\Cwmplan\HtmlView $this */

$isNew = ((int) $this->item->id === 0);
?>
<form action="<?php echo Route::_('index.php?option=com_livingword&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">
    <div class="main-card">
        <?php echo HTMLHelper::_('uitab.startTabSet', 'planTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

        <?php // --- Details Tab --- ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'planTab', 'details', Text::_('COM_LIVINGWORD_TAB_DETAILS')); ?>
        <div class="row">
            <div class="col-lg-9">
                <?php echo $this->form->renderField('alias'); ?>
                <?php echo $this->form->renderField('title'); ?>
                <?php echo $this->form->renderField('description'); ?>
                <?php echo $this->form->renderField('message'); ?>
            </div>
            <div class="col-lg-3">
                <?php echo $this->form->renderField('published'); ?>
                <?php echo $this->form->renderField('audio'); ?>
                <?php echo $this->form->renderField('audio_version'); ?>
                <?php if ((int) ($this->item->audio ?? 0) === 1 && !$isNew) : ?>
                    <div class="mb-3" id="audioPreviewContainer">
                        <button type="button" class="btn btn-sm btn-outline-info" id="testAudioBtn">
                            <span class="icon-play" aria-hidden="true"></span>
                            <?php echo Text::_('COM_LIVINGWORD_PLAN_TEST_AUDIO'); ?>
                        </button>
                        <audio id="testAudioPlayer" preload="none" class="d-none"></audio>
                        <small id="testAudioStatus" class="d-block mt-1 text-muted"></small>
                    </div>
                <?php endif; ?>
                <?php echo $this->form->renderField('testament'); ?>
                <?php echo $this->form->renderField('ordering'); ?>
                <?php echo $this->form->renderField('id'); ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php // --- Readings Tab --- ?>
        <?php
        $readingsCount = \is_array($this->item->readings ?? null) ? \count($this->item->readings) : 0;
        $readingsLabel = Text::_('COM_LIVINGWORD_PLAN_READINGS');
        if ($readingsCount > 0) {
            $readingsLabel .= ' <span class="badge bg-info">' . $readingsCount . '</span>';
        }
        ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'planTab', 'readings', $readingsLabel); ?>
        <div class="row">
            <div class="col-12">
                <?php if ($isNew) : ?>
                    <div class="alert alert-info">
                        <?php echo Text::_('COM_LIVINGWORD_SAVE_PLAN_FIRST'); ?>
                    </div>
                <?php else : ?>
                    <div class="d-flex justify-content-end gap-2 mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="batchMoveBtn" data-bs-toggle="modal" data-bs-target="#batchMoveModal" disabled>
                            <?php echo Text::_('COM_LIVINGWORD_MOVE_SELECTED'); ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#bulkImportModal">
                            <?php echo Text::_('COM_LIVINGWORD_BULK_IMPORT'); ?>
                        </button>
                    </div>
                    <?php echo $this->form->renderField('readings'); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<?php if (!$isNew) : ?>
<!-- Bulk Import Modal -->
<div class="modal fade" id="bulkImportModal" tabindex="-1" aria-labelledby="bulkImportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkImportModalLabel"><?php echo Text::_('COM_LIVINGWORD_BULK_IMPORT'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted"><?php echo Text::_('COM_LIVINGWORD_BULK_IMPORT_DESC'); ?></p>
                <textarea id="bulkImportText" class="form-control" rows="15" placeholder="<?php echo Text::_('COM_LIVINGWORD_BULK_IMPORT_PLACEHOLDER'); ?>"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCANCEL'); ?></button>
                <button type="button" class="btn btn-primary" id="bulkImportBtn"><?php echo Text::_('COM_LIVINGWORD_BULK_IMPORT'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Batch Move Modal -->
<div class="modal fade" id="batchMoveModal" tabindex="-1" aria-labelledby="batchMoveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchMoveModalLabel"><?php echo Text::_('COM_LIVINGWORD_MOVE_TO_POSITION'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted" id="batchMoveInfo"></p>
                <label for="batchMovePosition" class="form-label"><?php echo Text::_('COM_LIVINGWORD_TARGET_POSITION'); ?></label>
                <input type="number" id="batchMovePosition" class="form-control" min="1" value="1">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCANCEL'); ?></button>
                <button type="button" class="btn btn-primary" id="batchMoveExecBtn"><?php echo Text::_('COM_LIVINGWORD_MOVE'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Test Audio Preview
    var testBtn = document.getElementById('testAudioBtn');
    var testPlayer = document.getElementById('testAudioPlayer');
    var testStatus = document.getElementById('testAudioStatus');

    if (testBtn && testPlayer) {
        testBtn.addEventListener('click', function() {
            // Get audio version from the form field
            var audioVersion = document.querySelector('[name="jform[audio_version]"]');
            var version = audioVersion ? audioVersion.value : '';

            if (!version) {
                testStatus.textContent = '<?php echo Text::_('COM_LIVINGWORD_PLAN_TEST_AUDIO_NO_VERSION', true); ?>';
                testStatus.className = 'd-block mt-1 text-warning';
                return;
            }

            // Get the first reading from the subform
            var firstReading = document.querySelector('[name*="[reading]"]');
            var reading = firstReading ? firstReading.value : '';

            if (!reading) {
                testStatus.textContent = '<?php echo Text::_('COM_LIVINGWORD_PLAN_TEST_AUDIO_NO_READING', true); ?>';
                testStatus.className = 'd-block mt-1 text-warning';
                return;
            }

            testBtn.disabled = true;
            testStatus.textContent = '<?php echo Text::_('COM_LIVINGWORD_AUDIO_LOADING', true); ?>';
            testStatus.className = 'd-block mt-1 text-muted';

            var url = '<?php echo \Joomla\CMS\Router\Route::_('index.php?option=com_livingword&task=cwmaudio.getAudio&format=json', false); ?>'
                + '&reading=' + encodeURIComponent(reading)
                + '&version=' + encodeURIComponent(version)
                + '&<?php echo \Joomla\CMS\Session\Session::getFormToken(); ?>=1';

            fetch(url, { credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(json) {
                    if (json.success && json.data && json.data.audioUrl) {
                        testPlayer.src = json.data.audioUrl;
                        testPlayer.currentTime = 0;
                        testPlayer.play();
                        testStatus.textContent = '<?php echo Text::_('COM_LIVINGWORD_PLAN_TEST_AUDIO_PLAYING', true); ?>';
                        testStatus.className = 'd-block mt-1 text-success';

                        // Stop after 10 seconds
                        setTimeout(function() {
                            testPlayer.pause();
                            testPlayer.currentTime = 0;
                            testStatus.textContent = '<?php echo Text::_('COM_LIVINGWORD_PLAN_TEST_AUDIO_SUCCESS', true); ?>';
                        }, 10000);
                    } else {
                        testStatus.textContent = json.message || '<?php echo Text::_('COM_LIVINGWORD_AUDIO_UNAVAILABLE', true); ?>';
                        testStatus.className = 'd-block mt-1 text-danger';
                    }
                    testBtn.disabled = false;
                })
                .catch(function() {
                    testStatus.textContent = '<?php echo Text::_('COM_LIVINGWORD_AUDIO_ERROR', true); ?>';
                    testStatus.className = 'd-block mt-1 text-danger';
                    testBtn.disabled = false;
                });
        });
    }
});
</script>

<style>
.subform-repeatable-group.lw-selected { background-color: var(--template-bg-dark-3, #e8f0fe); }
.subform-repeatable-group .lw-row-actions { white-space: nowrap; }
.subform-repeatable-group .lw-row-select { margin-right: 0.5rem; }
.subform-repeatable-group .lw-row-num {
    display: inline-block; min-width: 2.5em; text-align: center;
    font-weight: bold; color: var(--template-text-dark, #666);
    cursor: pointer; border-bottom: 1px dashed currentColor;
}
.subform-repeatable-group .lw-row-num:hover { color: var(--template-link-color, #0d6efd); }
.subform-repeatable-group .lw-pos-input {
    width: 3.5em; text-align: center; font-size: 0.85rem;
    padding: 0.1rem 0.25rem;
}
.lw-move-btn { padding: 0.15rem 0.35rem; font-size: 0.75rem; line-height: 1; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.subform-repeatable');
    if (!container) return;

    // --- Row numbering, selection checkboxes, and move buttons ---
    function enhanceRows() {
        const rows = container.querySelectorAll('.subform-repeatable-group');
        rows.forEach(function(row, idx) {
            // Add row number + select checkbox + move buttons if not already present
            if (row.querySelector('.lw-row-num')) {
                row.querySelector('.lw-row-num').textContent = idx + 1;
                return;
            }

            const firstCell = row.querySelector('td, .subform-repeatable-group-column');
            if (!firstCell) return;

            const controls = document.createElement('span');
            controls.className = 'lw-row-actions d-flex align-items-center gap-1 me-2';
            controls.innerHTML =
                '<input type="checkbox" class="form-check-input lw-row-select" title="Select">' +
                '<span class="lw-row-num">' + (idx + 1) + '</span>' +
                '<span class="btn-group-vertical">' +
                    '<button type="button" class="btn btn-outline-secondary lw-move-btn lw-move-up" title="Move up">' +
                        '<span class="icon-chevron-up" aria-hidden="true"></span>' +
                    '</button>' +
                    '<button type="button" class="btn btn-outline-secondary lw-move-btn lw-move-down" title="Move down">' +
                        '<span class="icon-chevron-down" aria-hidden="true"></span>' +
                    '</button>' +
                '</span>';

            firstCell.insertBefore(controls, firstCell.firstChild);
        });

        updateBatchBtn();
    }

    // Re-enhance after Joomla adds/removes rows
    const observer = new MutationObserver(function() {
        setTimeout(enhanceRows, 100);
    });
    observer.observe(container, { childList: true, subtree: true });

    // Initial enhancement
    setTimeout(enhanceRows, 500);

    // --- Click row number to jump to position ---
    container.addEventListener('click', function(e) {
        const numEl = e.target.closest('.lw-row-num');
        if (!numEl || numEl.querySelector('input')) return;

        const currentPos = parseInt(numEl.textContent, 10);
        const total = container.querySelectorAll('.subform-repeatable-group').length;

        const input = document.createElement('input');
        input.type = 'number';
        input.className = 'form-control form-control-sm lw-pos-input';
        input.min = 1;
        input.max = total;
        input.value = currentPos;

        numEl.textContent = '';
        numEl.appendChild(input);
        input.focus();
        input.select();

        function commitMove() {
            const newPos = parseInt(input.value, 10);
            numEl.textContent = currentPos; // Restore temporarily

            if (isNaN(newPos) || newPos < 1 || newPos > total || newPos === currentPos) {
                renumberRows();
                return;
            }

            const row = numEl.closest('.subform-repeatable-group');
            const allRows = Array.from(container.querySelectorAll('.subform-repeatable-group'));
            const parent = row.parentNode;

            // Remove from current position
            allRows.splice(currentPos - 1, 1);
            // Insert at new position
            allRows.splice(newPos - 1, 0, row);

            // Reorder DOM
            allRows.forEach(function(r) { parent.appendChild(r); });
            renumberRows();
        }

        input.addEventListener('blur', commitMove);
        input.addEventListener('keydown', function(ev) {
            if (ev.key === 'Enter') { ev.preventDefault(); input.blur(); }
            if (ev.key === 'Escape') { numEl.textContent = currentPos; }
        });
    });

    // --- Move up/down ---
    container.addEventListener('click', function(e) {
        const btn = e.target.closest('.lw-move-up, .lw-move-down');
        if (!btn) return;

        e.preventDefault();
        const row = btn.closest('.subform-repeatable-group');
        const parent = row.parentNode;

        if (btn.classList.contains('lw-move-up') && row.previousElementSibling) {
            parent.insertBefore(row, row.previousElementSibling);
        } else if (btn.classList.contains('lw-move-down') && row.nextElementSibling) {
            parent.insertBefore(row.nextElementSibling, row);
        }

        renumberRows();
    });

    // --- Selection tracking ---
    container.addEventListener('change', function(e) {
        if (!e.target.classList.contains('lw-row-select')) return;
        e.target.closest('.subform-repeatable-group').classList.toggle('lw-selected', e.target.checked);
        updateBatchBtn();
    });

    function getSelectedRows() {
        return container.querySelectorAll('.lw-row-select:checked');
    }

    function updateBatchBtn() {
        const btn = document.getElementById('batchMoveBtn');
        if (btn) {
            const count = getSelectedRows().length;
            btn.disabled = count === 0;
        }
    }

    function renumberRows() {
        container.querySelectorAll('.subform-repeatable-group').forEach(function(row, idx) {
            const num = row.querySelector('.lw-row-num');
            if (num) num.textContent = idx + 1;
        });
    }

    // --- Batch move ---
    document.getElementById('batchMoveModal')?.addEventListener('show.bs.modal', function() {
        const count = getSelectedRows().length;
        const total = container.querySelectorAll('.subform-repeatable-group').length;
        document.getElementById('batchMoveInfo').textContent =
            count + ' reading(s) selected. Total: ' + total + ' readings.';
        document.getElementById('batchMovePosition').max = total;
    });

    document.getElementById('batchMoveExecBtn')?.addEventListener('click', function() {
        const targetPos = parseInt(document.getElementById('batchMovePosition').value, 10) - 1;
        const allRows = Array.from(container.querySelectorAll('.subform-repeatable-group'));
        const selected = [];
        const remaining = [];

        allRows.forEach(function(row) {
            const cb = row.querySelector('.lw-row-select');
            if (cb && cb.checked) {
                selected.push(row);
                cb.checked = false;
                row.classList.remove('lw-selected');
            } else {
                remaining.push(row);
            }
        });

        if (selected.length === 0) return;

        // Insert selected rows at target position
        const insertAt = Math.max(0, Math.min(targetPos, remaining.length));
        remaining.splice(insertAt, 0, ...selected);

        // Reorder DOM
        const parent = allRows[0].parentNode;
        remaining.forEach(function(row) { parent.appendChild(row); });

        renumberRows();
        updateBatchBtn();

        const modal = bootstrap.Modal.getInstance(document.getElementById('batchMoveModal'));
        if (modal) modal.hide();
    });

    // --- Bulk import ---
    document.getElementById('bulkImportBtn')?.addEventListener('click', function() {
        const text = document.getElementById('bulkImportText').value.trim();
        if (!text) return;

        const lines = text.split('\n').map(l => l.trim()).filter(l => l.length > 0);

        let delay = 0;
        lines.forEach(function(line) {
            setTimeout(function() {
                const addBtn = container.querySelector('.group-add, [data-btn-add]');
                if (addBtn) addBtn.click();

                setTimeout(function() {
                    const rows = container.querySelectorAll('.subform-repeatable-group');
                    const lastRow = rows[rows.length - 1];
                    if (lastRow) {
                        const readingInput = lastRow.querySelector('[name*="[reading]"]');
                        if (readingInput) {
                            readingInput.value = line;
                            readingInput.dispatchEvent(new Event('change'));
                        }
                    }
                }, 50);
            }, delay);
            delay += 100;
        });

        setTimeout(function() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('bulkImportModal'));
            if (modal) modal.hide();
            document.getElementById('bulkImportText').value = '';
            renumberRows();
        }, delay + 200);
    });
});
</script>
<?php endif; ?>
