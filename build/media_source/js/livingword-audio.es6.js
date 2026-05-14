/**
 * LivingWord Audio Player
 *
 * Fetches audio URLs from the server-side controller (keeping API keys private)
 * and provides an HTML5 audio player with optional verse-level sync.
 *
 * @package  Livingword
 * @since    5.2.0
 */
((document) => {
  'use strict';

  /**
   * Initialize all audio player containers on the page.
   */
  const init = () => {
    const players = document.querySelectorAll('[data-livingword-audio]');

    players.forEach((container) => {
      const reading = container.dataset.reading || '';
      const version = container.dataset.version || 'kjv';
      const baseUrl = container.dataset.audioUrl || '';

      if (!reading || !baseUrl) {
        return;
      }

      setupPlayer(container, reading, version, baseUrl);
    });
  };

  /**
   * Set up an individual audio player.
   *
   * @param {HTMLElement} container  The player container element
   * @param {string}      reading    Scripture reference
   * @param {string}      version    Bible translation code
   * @param {string}      baseUrl    AJAX endpoint URL
   */
  const setupPlayer = (container, reading, version, baseUrl) => {
    const playBtn = container.querySelector('.livingword-audio-play');
    const audioEl = container.querySelector('audio');
    const statusEl = container.querySelector('.livingword-audio-status');

    if (!playBtn || !audioEl) {
      return;
    }

    let loaded = false;
    let audioData = null;

    playBtn.addEventListener('click', async () => {
      if (!loaded) {
        playBtn.disabled = true;

        if (statusEl) {
          statusEl.textContent = Joomla.Text._('COM_LIVINGWORD_AUDIO_LOADING') || 'Loading audio...';
          statusEl.classList.remove('d-none');
        }

        try {
          audioData = await fetchAudio(baseUrl, reading, version);
        } catch (e) {
          if (statusEl) {
            statusEl.textContent = Joomla.Text._('COM_LIVINGWORD_AUDIO_ERROR') || 'Audio unavailable';
            statusEl.classList.add('text-danger');
          }

          playBtn.disabled = false;

          return;
        }

        if (!audioData || !audioData.data || !audioData.data.audioUrl) {
          if (statusEl) {
            statusEl.textContent = Joomla.Text._('COM_LIVINGWORD_AUDIO_UNAVAILABLE') || 'No audio found';
          }

          playBtn.disabled = false;

          return;
        }

        // Build playlist from audioFiles array (multi-chapter support)
        const playlist = (audioData.data.audioFiles || [audioData.data]).map(f => f.audioUrl);
        let currentTrack = 0;

        audioEl.src = playlist[0];
        loaded = true;
        playBtn.disabled = false;

        if (statusEl) {
          statusEl.classList.add('d-none');
        }

        // Show track count for multi-chapter readings
        if (playlist.length > 1 && statusEl) {
          statusEl.textContent = '1 / ' + playlist.length;
          statusEl.classList.remove('d-none');
        }

        // Set up verse timing sync if available
        if (audioData.data.verseTiming && audioData.data.verseTiming.length > 0) {
          setupVerseSync(container, audioEl, audioData.data.verseTiming);
        }

        // Auto-advance to next chapter when current ends
        audioEl.addEventListener('ended', () => {
          currentTrack++;

          if (currentTrack < playlist.length) {
            audioEl.src = playlist[currentTrack];
            audioEl.play();

            if (statusEl && playlist.length > 1) {
              statusEl.textContent = (currentTrack + 1) + ' / ' + playlist.length;
            }
          } else {
            // All chapters done — reset
            currentTrack = 0;
            audioEl.src = playlist[0];
            setPlayIcon();

            if (statusEl && playlist.length > 1) {
              statusEl.textContent = '';
              statusEl.classList.add('d-none');
            }
          }
        });
      }

      if (audioEl.paused) {
        audioEl.play();
        setPauseIcon();
      } else {
        audioEl.pause();
        setPlayIcon();
      }
    });

    const setPlayIcon = () => {
      playBtn.querySelector('.icon-pause, .fa-pause')?.classList.replace('icon-pause', 'icon-play');
      playBtn.querySelector('.fa-pause')?.classList.replace('fa-pause', 'fa-play');
      playBtn.setAttribute('aria-label', Joomla.Text._('COM_LIVINGWORD_AUDIO_PLAY') || 'Play');
    };

    const setPauseIcon = () => {
      playBtn.querySelector('.icon-play, .fa-play')?.classList.replace('icon-play', 'icon-pause');
      playBtn.querySelector('.fa-play')?.classList.replace('fa-play', 'fa-pause');
      playBtn.setAttribute('aria-label', Joomla.Text._('COM_LIVINGWORD_AUDIO_PAUSE') || 'Pause');
    };
  };

  /**
   * Fetch audio data from the server controller.
   *
   * @param {string} baseUrl  The controller AJAX URL
   * @param {string} reading  Scripture reference
   * @param {string} version  Bible translation code
   *
   * @returns {Promise<object>}
   */
  const fetchAudio = async (baseUrl, reading, version) => {
    const url = new URL(baseUrl, window.location.origin);

    url.searchParams.set('reading', reading);
    url.searchParams.set('version', version);
    url.searchParams.set(Joomla.getOptions('csrf.token', ''), '1');

    const response = await fetch(url.toString(), {
      method: 'GET',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });

    if (!response.ok) {
      throw new Error('HTTP ' + response.status);
    }

    return response.json();
  };

  /**
   * Set up verse-level text highlighting synchronized to audio playback.
   *
   * Looks for elements with data-verse="N" inside the container and
   * highlights the active verse based on audio timestamps.
   *
   * @param {HTMLElement}   container    The player container
   * @param {HTMLAudioElement} audioEl   The audio element
   * @param {Array}         verseTiming  Array of {verse, timestamp_start, timestamp_end}
   */
  const setupVerseSync = (container, audioEl, verseTiming) => {
    const scriptureContainer = container.closest('.livingword-today-reading')
      ?.querySelector('.livingword-scripture-text');

    if (!scriptureContainer) {
      return;
    }

    audioEl.addEventListener('timeupdate', () => {
      const currentTime = audioEl.currentTime;

      // Remove all active highlights
      scriptureContainer.querySelectorAll('.verse-active').forEach((el) => {
        el.classList.remove('verse-active');
      });

      // Find and highlight the current verse
      for (const timing of verseTiming) {
        if (currentTime >= timing.timestamp_start && currentTime < timing.timestamp_end) {
          const verseEl = scriptureContainer.querySelector('[data-verse="' + timing.verse + '"]');

          if (verseEl) {
            verseEl.classList.add('verse-active');
          }

          break;
        }
      }
    });
  };

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(document);