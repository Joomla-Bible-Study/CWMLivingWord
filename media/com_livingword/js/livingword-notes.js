/**
 * @package    Livingword
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 *
 * Auto-saving reading notes/journal with debounce.
 */
document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  var container = document.querySelector('[data-livingword-notes]');
  if (!container) return;

  var textarea = container.querySelector('.livingword-notes-textarea');
  var status = container.querySelector('.livingword-notes-status');
  var saveUrl = container.dataset.notesUrl;
  var planId = container.dataset.planId;
  var day = container.dataset.day;
  var csrfToken = Joomla.getOptions('csrf.token');
  var debounceTimer = null;
  var saving = false;

  function setStatus(text, cssClass) {
    if (!status) return;
    status.textContent = text;
    status.className = 'livingword-notes-status small ' + (cssClass || 'text-muted');
  }

  function saveNote() {
    if (saving) return;
    saving = true;
    setStatus(Joomla.Text._('COM_LIVINGWORD_NOTES_SAVING') || 'Saving...', 'text-muted');

    var url = saveUrl
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
