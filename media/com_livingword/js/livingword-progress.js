/**
 * LivingWord — Reading completion toggle
 *
 * Handles "Mark as Read" buttons via AJAX. Requires a CSRF token
 * passed via Joomla's script options (csrf.token).
 *
 * @since 5.3.0
 */
((document) => {
  'use strict';

  const init = () => {
    document.querySelectorAll('[data-livingword-progress]').forEach(setupToggle);
  };

  const setupToggle = (container) => {
    const btn = container.querySelector('.livingword-mark-read-btn');
    if (!btn) return;

    let busy = false;

    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      if (busy) return;
      busy = true;

      const planId = container.dataset.planId;
      const day = container.dataset.day;
      const tokenName = Joomla.getOptions('csrf.token') || '';

      const url = container.dataset.progressUrl
        + '&plan_id=' + encodeURIComponent(planId)
        + '&day=' + encodeURIComponent(day)
        + '&' + encodeURIComponent(tokenName) + '=1';

      btn.disabled = true;

      try {
        const response = await fetch(url, { method: 'GET', credentials: 'same-origin' });
        if (!response.ok) throw new Error('HTTP ' + response.status);

        const json = await response.json();

        if (json.success && json.data) {
          const completed = json.data.completed;
          container.dataset.completed = completed ? '1' : '0';

          // Update button text and icon
          const icon = btn.querySelector('.icon-checkmark, .icon-checkbox-unchecked, .icon-check, .icon-unchecked');
          if (completed) {
            btn.classList.add('btn-success');
            btn.classList.remove('btn-outline-secondary');
            if (icon) icon.className = 'icon-checkmark';
            btn.setAttribute('aria-label', btn.dataset.labelRead || 'Mark as Unread');
          } else {
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-secondary');
            if (icon) icon.className = 'icon-checkbox-unchecked';
            btn.setAttribute('aria-label', btn.dataset.labelUnread || 'Mark as Read');
          }

          // Update any checkmark indicators on the page for this day
          document.querySelectorAll('[data-progress-day="' + day + '"]').forEach(el => {
            el.classList.toggle('livingword-completed', completed);
          });
        } else {
          console.error('LivingWord progress error:', json.message || 'Unknown error');
        }
      } catch (err) {
        console.error('LivingWord progress fetch error:', err);
      } finally {
        btn.disabled = false;
        busy = false;
      }
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(document);
