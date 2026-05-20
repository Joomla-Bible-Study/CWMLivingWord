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

/** @var \Joomla\CMS\Document\HtmlDocument $doc */
$this->getDocument()->getWebAssetManager()
    ->useScript('form.validate')
    ->useScript('bootstrap.modal');
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
                <?php if (!$isNew) : ?>
                    <div class="mb-3" id="audioPreviewContainer" style="display: none;">
                        <button type="button" class="btn btn-sm btn-outline-info" id="testAudioBtn">
                            <span class="icon-play" aria-hidden="true"></span>
                            <?php echo Text::_('COM_LIVINGWORD_PLAN_TEST_AUDIO'); ?>
                        </button>
                        <audio id="testAudioPlayer" preload="none" class="d-none"></audio>
                        <small id="testAudioStatus" class="d-block mt-1 text-muted"></small>
                    </div>
                <?php endif; ?>
                <?php echo $this->form->renderField('testament'); ?>
                <?php echo $this->form->renderField('tags'); ?>
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

<!-- Devotional Editor Modal -->
<div class="modal fade" id="devotionalModal" tabindex="-1" aria-labelledby="devotionalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="devotionalModalLabel">
                    <span id="devotionalDayLabel"></span>
                    — <?php echo Text::_('COM_LIVINGWORD_READING_DEVOTIONAL'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo $this->form->renderField('devotional_editor_content'); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCANCEL'); ?></button>
                <button type="button" class="btn btn-success" id="devotionalSaveBtn">
                    <span class="icon-save" aria-hidden="true"></span>
                    <?php echo Text::_('JAPPLY'); ?>
                </button>
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
    // Toggle Test Audio button with Enable Audio switch
    var audioContainer = document.getElementById('audioPreviewContainer');
    var audioRadios = document.querySelectorAll('[name="jform[audio]"]');

    function updateAudioPreviewVisibility() {
        var checked = document.querySelector('[name="jform[audio]"]:checked');
        var isOn = checked && checked.value === '1';
        if (audioContainer) {
            audioContainer.style.display = isOn ? '' : 'none';
        }
    }

    audioRadios.forEach(function(radio) {
        radio.addEventListener('change', updateAudioPreviewVisibility);
    });
    updateAudioPreviewVisibility();

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
/* --- Readings subform table --- */
.subform-repeatable table { table-layout: fixed; width: 100%; }

/* Column widths: # | Reading | Devotional | Audio | Actions */
.subform-repeatable table .lw-col-order { width: 3.5em; }
.subform-repeatable table thead th:nth-child(2) { width: auto; }
.subform-repeatable table .lw-col-devotional { width: 7em; text-align: center; }
.subform-repeatable table thead th:nth-child(4) { width: 30%; }
.subform-repeatable table thead th:last-child { width: 7em; }

/* Compact cell padding */
.subform-repeatable-group td { padding: 0.35rem 0.5rem; vertical-align: middle; }

/* Compact inputs */
.subform-repeatable-group input[type="text"],
.subform-repeatable-group input[type="url"] { font-size: 0.875rem; padding: 0.3rem 0.5rem; }

/* Subtle row separator — override Joomla table borders for dark mode */
.subform-repeatable table,
.subform-repeatable table th,
.subform-repeatable table td { border-color: var(--template-bg-dark-7, rgba(255,255,255,0.1)); }
.subform-repeatable-group { border-bottom: 1px solid var(--template-bg-dark-7, rgba(255,255,255,0.1)); }

/* Batch move button: muted but visible when disabled in dark mode */
#batchMoveBtn:disabled {
    opacity: 1;
    border-color: #5a6268;
    color: #adb5bd;
}

/* Selected row highlight — works in both light and dark mode */
.subform-repeatable-group.lw-selected { background-color: rgba(13, 110, 253, 0.15); }

/* Ordering column */
.lw-col-order { text-align: center; vertical-align: middle; }
.lw-row-num {
    display: inline-block; min-width: 2em; text-align: center;
    font-weight: 600; font-size: 0.85rem;
    color: var(--template-text-dark, #999);
}
.lw-pos-input {
    width: 3em; text-align: center; font-size: 0.8rem;
    padding: 0.1rem 0.2rem;
}

/* Devotional button in its own column */
.lw-col-devotional { text-align: center; vertical-align: middle; }
.lw-devotional-btn { font-size: 0.8rem; padding: 0.15rem 0.5rem; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.subform-repeatable');
    if (!container) return;

    // --- Add ordering header column ---
    function addOrderHeader() {
        const thead = container.querySelector('thead tr');
        if (!thead || thead.querySelector('.lw-col-order')) return;
        const th = document.createElement('th');
        th.className = 'lw-col-order';
        th.textContent = '#';
        thead.insertBefore(th, thead.firstChild);

        // Add devotional column header after Reading column
        if (!thead.querySelector('.lw-col-devotional')) {
            const devTh = document.createElement('th');
            devTh.className = 'lw-col-devotional';
            devTh.textContent = '<?php echo Text::_('COM_LIVINGWORD_READING_DEVOTIONAL_SHORT', true); ?>';
            const readingTh = thead.children[1]; // Reading is 2nd column (after #)
            readingTh.after(devTh);
        }
    }

    // --- Row numbering, selection checkboxes, and move buttons ---
    function enhanceRows() {
        addOrderHeader();
        const rows = container.querySelectorAll('.subform-repeatable-group');
        rows.forEach(function(row, idx) {
            // Update existing row number
            if (row.querySelector('.lw-row-num')) {
                row.querySelector('.lw-row-num').textContent = idx + 1;
                return;
            }

            // Create a new cell for ordering controls
            const orderCell = document.createElement('td');
            orderCell.className = 'lw-col-order';
            orderCell.innerHTML =
                '<div class="d-flex align-items-center gap-1 justify-content-center">' +
                    '<input type="checkbox" class="form-check-input lw-row-select m-0" title="<?php echo Text::_('COM_LIVINGWORD_SELECT_FOR_BATCH_MOVE', true); ?>">' +
                    '<span class="lw-row-num">' + (idx + 1) + '</span>' +
                '</div>';

            row.insertBefore(orderCell, row.firstChild);
        });

        updateBatchBtn();
    }

    // Combined enhancement: ordering + devotional buttons in one pass
    let enhancePending = false;
    function enhanceAll() {
        enhancePending = false;
        enhanceRows();
        addDevotionalButtons();
    }
    function scheduleEnhance() {
        if (enhancePending) return;
        enhancePending = true;
        requestAnimationFrame(enhanceAll);
    }

    // Pause observer during our own DOM changes to prevent re-triggering
    let observing = false;
    function enhanceAllGuarded() {
        observer.disconnect();
        enhanceAll();
        observer.observe(container, { childList: true, subtree: true });
    }

    // Observe for rows added/removed (initial render, add/remove buttons, bulk import)
    const observer = new MutationObserver(function() {
        if (enhancePending) return;
        enhancePending = true;
        requestAnimationFrame(enhanceAllGuarded);
    });
    observer.observe(container, { childList: true, subtree: true });

    // If rows already exist, enhance immediately; otherwise the observer handles it
    if (container.querySelector('.subform-repeatable-group')) {
        enhanceAll();
    }

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
            btn.className = 'btn btn-sm ' + (count > 0 ? 'btn-primary' : 'btn-outline-primary');
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

    // --- Devotional editor modal ---
    let activeDevotionalField = null;
    const devotionalModal = document.getElementById('devotionalModal');
    const devotionalDayLabel = document.getElementById('devotionalDayLabel');
    const editorFieldName = 'jform_devotional_editor_content';

    function getEditorInstance() {
        return typeof JoomlaEditor !== 'undefined' ? JoomlaEditor.get(editorFieldName) : null;
    }

    function addDevotionalButtons() {
        const rows = container.querySelectorAll('.subform-repeatable-group');
        rows.forEach(function(row, idx) {
            if (row.querySelector('.lw-col-devotional')) return;

            const descripField = row.querySelector('input[name*="[descrip]"]');
            if (!descripField) return;

            const readingField = row.querySelector('input[name*="[reading]"]');
            if (!readingField) return;

            const content = descripField.value || '';
            const hasContent = content.trim().length > 0;

            const cell = document.createElement('td');
            cell.className = 'lw-col-devotional';

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm lw-devotional-btn ' + (hasContent ? 'btn-info' : 'btn-outline-secondary');
            btn.title = '<?php echo Text::_('COM_LIVINGWORD_READING_DEVOTIONAL', true); ?>';
            btn.innerHTML = '<span class="icon-pencil-alt" aria-hidden="true"></span> <?php echo Text::_('JACTION_EDIT', true); ?>';

            btn.addEventListener('click', function() {
                activeDevotionalField = descripField;
                const dayNum = row.querySelector('.lw-row-num');
                devotionalDayLabel.textContent = 'Day ' + (dayNum ? dayNum.textContent : (idx + 1))
                    + (readingField ? ' \u2014 ' + readingField.value : '');

                const editor = getEditorInstance();
                if (editor) {
                    editor.setValue(descripField.value || '');
                }

                devotionalModal.open ? devotionalModal.open() : bootstrap.Modal.getOrCreateInstance(devotionalModal).show();
            });

            cell.appendChild(btn);

            // Insert after the reading cell (2nd column, index 1)
            const readingCell = readingField.closest('td');
            readingCell.after(cell);
        });
    }

    // Move focus out before modal hides to prevent aria-hidden warning
    devotionalModal.addEventListener('hide.bs.modal', function() {
        if (devotionalModal.contains(document.activeElement)) {
            document.activeElement.blur();
        }
    });

    // Save devotional content back to hidden field
    document.getElementById('devotionalSaveBtn')?.addEventListener('click', function() {
        if (!activeDevotionalField) return;

        const editor = getEditorInstance();
        if (editor) {
            activeDevotionalField.value = editor.getValue();
        }

        // Update the button appearance
        const row = activeDevotionalField.closest('.subform-repeatable-group');
        const btn = row?.querySelector('.lw-devotional-btn');
        if (btn) {
            const hasContent = activeDevotionalField.value.trim().length > 0;
            btn.className = 'btn btn-sm lw-devotional-btn ' + (hasContent ? 'btn-info' : 'btn-outline-secondary');
        }

        devotionalModal.close ? devotionalModal.close() : bootstrap.Modal.getInstance(devotionalModal)?.hide();
        activeDevotionalField = null;
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
