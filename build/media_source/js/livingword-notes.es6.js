/**
 * @package    Livingword
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Auto-saving reading notes/journal with debounce.
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const container = document.querySelector('[data-livingword-notes]');
    if (!container) return;

    const textarea = container.querySelector('.livingword-notes-textarea');
    const status = container.querySelector('.livingword-notes-status');
    const saveUrl = container.dataset.notesUrl;
    const planId = container.dataset.planId;
    const day = container.dataset.day;
    const csrfToken = Joomla.getOptions('csrf.token');
    let debounceTimer = null;
    let saving = false;

    function setStatus(text, cssClass) {
        if (!status) return;
        status.textContent = text;
        status.className = 'livingword-notes-status small ' + (cssClass || 'text-muted');
    }

    function saveNote() {
        if (saving) return;
        saving = true;
        setStatus(Joomla.Text._('COM_LIVINGWORD_NOTES_SAVING') || 'Saving...', 'text-muted');

        const url = saveUrl
      + '&plan_id=' + encodeURIComponent(planId)
      + '&day=' + encodeURIComponent(day)
      + '&note_text=' + encodeURIComponent(textarea.value)
      + '&' + csrfToken + '=1';

        fetch(url, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.json(); })
            .then(function (json) {
                saving = false;
                if (json.success) {
                    setStatus(Joomla.Text._('COM_LIVINGWORD_NOTES_SAVED') || 'Saved', 'text-success');
                } else {
                    setStatus(Joomla.Text._('COM_LIVINGWORD_NOTES_ERROR') || 'Error', 'text-danger');
                }
            })
            .catch(function () {
                saving = false;
                setStatus(Joomla.Text._('COM_LIVINGWORD_NOTES_ERROR') || 'Error', 'text-danger');
            });
    }

    if (textarea) {
        textarea.addEventListener('input', function () {
            setStatus('', '');
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(saveNote, 800);
        });
    }
});
