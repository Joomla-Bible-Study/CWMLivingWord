/**
 * LivingWord — Reading completion toggle
 *
 * Handles "Mark as Read" buttons via AJAX for both day-level
 * and passage-level completion. Requires a CSRF token
 * passed via Joomla's script options (csrf.token).
 *
 * @since 5.3.0
 */
((document) => {
  'use strict';

  const init = () => {
    // Day-level toggle buttons (mark all read/unread)
    document.querySelectorAll('[data-livingword-progress]').forEach(setupDayToggle);
    // Passage-level toggle buttons
    document.querySelectorAll('[data-livingword-passage-toggle]').forEach(setupPassageToggle);
  };

  const getTokenName = () => Joomla.getOptions('csrf.token') || '';

  const setupDayToggle = (container) => {
    const btn = container.querySelector('.livingword-mark-read-btn');
    if (!btn) return;

    let busy = false;

    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      if (busy) return;
      busy = true;

      const planId = container.dataset.planId;
      const day = container.dataset.day;
      const passageCount = container.dataset.passageCount || '1';

      const url = container.dataset.progressUrl
        + '&plan_id=' + encodeURIComponent(planId)
        + '&day=' + encodeURIComponent(day)
        + '&passage_count=' + encodeURIComponent(passageCount)
        + '&' + encodeURIComponent(getTokenName()) + '=1';

      btn.disabled = true;

      try {
        const response = await fetch(url, { method: 'GET', credentials: 'same-origin' });
        if (!response.ok) throw new Error('HTTP ' + response.status);

        const json = await response.json();

        if (json.success && json.data) {
          const completed = json.data.completed;
          container.dataset.completed = completed ? '1' : '0';

          updateDayButton(btn, completed);
          updateDayIndicators(day, completed);

          // Update all passage buttons on the page to match
          updateAllPassageButtons(completed);

          // Update passage counter text
          updatePassageCounter(completed ? parseInt(passageCount) : 0, parseInt(passageCount));
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

  const setupPassageToggle = (btn) => {
    let busy = false;

    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      if (busy) return;
      busy = true;

      const planId = btn.dataset.planId;
      const day = btn.dataset.day;
      const passageIndex = btn.dataset.passageIndex;
      const passageCount = btn.dataset.passageCount || '1';

      const url = btn.dataset.progressUrl
        + '&plan_id=' + encodeURIComponent(planId)
        + '&day=' + encodeURIComponent(day)
        + '&passage_index=' + encodeURIComponent(passageIndex)
        + '&passage_count=' + encodeURIComponent(passageCount)
        + '&' + encodeURIComponent(getTokenName()) + '=1';

      btn.disabled = true;

      try {
        const response = await fetch(url, { method: 'GET', credentials: 'same-origin' });
        if (!response.ok) throw new Error('HTTP ' + response.status);

        const json = await response.json();

        if (json.success && json.data) {
          const passageCompleted = json.data.passage_completed;
          const dayCompleted = json.data.completed;

          // Update this passage button
          btn.dataset.completed = passageCompleted ? '1' : '0';
          updatePassageButton(btn, passageCompleted);

          // Update the passage text styling
          const passageItem = btn.closest('[data-livingword-passage]');
          if (passageItem) {
            const lead = passageItem.querySelector('.livingword-scripture-text, .lead, p');
            if (lead) {
              lead.classList.toggle('text-decoration-line-through', passageCompleted);
              lead.classList.toggle('text-muted', passageCompleted);
            }
          }

          // Update the day-level "Mark All" button if present
          const dayContainer = document.querySelector('[data-livingword-progress]');
          if (dayContainer) {
            dayContainer.dataset.completed = dayCompleted ? '1' : '0';
            const dayBtn = dayContainer.querySelector('.livingword-mark-read-btn');
            if (dayBtn) {
              updateDayButton(dayBtn, dayCompleted);
            }
          }

          // Update passage counter
          const completedCount = document.querySelectorAll('[data-livingword-passage-toggle][data-completed="1"]').length;
          const totalCount = parseInt(passageCount);
          updatePassageCounter(completedCount, totalCount);

          updateDayIndicators(day, dayCompleted);
        } else {
          console.error('LivingWord passage error:', json.message || 'Unknown error');
        }
      } catch (err) {
        console.error('LivingWord passage fetch error:', err);
      } finally {
        btn.disabled = false;
        busy = false;
      }
    });
  };

  const updateDayButton = (btn, completed) => {
    const icon = btn.querySelector('[class^="icon-"]');
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

    // Update button text node (last text content after icon)
    const textNodes = Array.from(btn.childNodes).filter(n => n.nodeType === Node.TEXT_NODE);
    if (textNodes.length > 0) {
      const label = completed
        ? (btn.dataset.labelRead || 'All Completed')
        : (btn.dataset.labelUnread || 'Mark All as Read');
      textNodes[textNodes.length - 1].textContent = '\n                                ' + label + '\n                            ';
    }
  };

  const updatePassageButton = (btn, completed) => {
    const icon = btn.querySelector('[class^="icon-"]');
    if (completed) {
      btn.classList.add('btn-success');
      btn.classList.remove('btn-outline-secondary');
      if (icon) icon.className = 'icon-checkmark';
    } else {
      btn.classList.remove('btn-success');
      btn.classList.add('btn-outline-secondary');
      if (icon) icon.className = 'icon-checkbox-unchecked';
    }
  };

  const updateAllPassageButtons = (completed) => {
    document.querySelectorAll('[data-livingword-passage-toggle]').forEach(btn => {
      btn.dataset.completed = completed ? '1' : '0';
      updatePassageButton(btn, completed);

      const passageItem = btn.closest('[data-livingword-passage]');
      if (passageItem) {
        const lead = passageItem.querySelector('.livingword-scripture-text, .lead, p');
        if (lead) {
          lead.classList.toggle('text-decoration-line-through', completed);
          lead.classList.toggle('text-muted', completed);
        }
      }
    });
  };

  const updatePassageCounter = (completedCount, totalCount) => {
    const dayContainer = document.querySelector('[data-livingword-progress]');
    if (!dayContainer) return;

    const counter = dayContainer.querySelector('.text-muted');
    if (counter && totalCount > 1) {
      counter.textContent = completedCount + ' of ' + totalCount + ' passages completed';
    }
  };

  const updateDayIndicators = (day, completed) => {
    document.querySelectorAll('[data-progress-day="' + day + '"]').forEach(el => {
      el.classList.toggle('livingword-completed', completed);
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(document);
