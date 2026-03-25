<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;

/**
 * Scripture helper — bridges LivingWord with lib_cwmscripture.
 *
 * Provides passage text retrieval, reference formatting, and link generation
 * using the CWM Scripture Library when available, with graceful fallback.
 *
 * @since  5.1.0
 */
class CwmscriptureHelper
{
    /**
     * Check if the CWM Scripture Library is installed and available.
     *
     * @return  bool
     *
     * @since   5.1.0
     */
    public static function isLibraryAvailable(): bool
    {
        if (!class_exists('CWM\\Library\\Scripture\\LibraryVersion')) {
            return false;
        }

        return \CWM\Library\Scripture\LibraryVersion::isInstalled();
    }

    /**
     * Get passage text for a reading reference using lib_cwmscripture.
     *
     * Handles semicolon-separated multi-passage references by fetching each
     * passage individually and concatenating the results.
     *
     * @param   string  $reading   The human-readable reading reference (e.g. "Genesis 1-3; Psalm 23")
     * @param   string  $version   Bible translation code (e.g. "kjv", "nlt")
     *
     * @return  ?object  Object with text, reference, copyright, or null if unavailable
     *
     * @since   5.1.0
     */
    public static function getPassageText(string $reading, string $version): ?object
    {
        if (!self::isLibraryAvailable() || empty($reading)) {
            return null;
        }

        $params   = \CWM\Library\Scripture\Helper\ScriptureParamsHelper::getParams();
        $provider = \CWM\Library\Scripture\Bible\BibleProviderFactory::getProviderForTranslation($version, $params);

        $passages  = array_map('trim', explode(';', $reading));
        $allText   = [];
        $copyright = '';

        foreach ($passages as $passage) {
            if (empty($passage)) {
                continue;
            }

            try {
                $result = $provider->getPassage($passage, $version);
            } catch (\Exception) {
                continue;
            }

            if ($result !== null && $result->hasText()) {
                $allText[] = '<div class="scripture-passage">'
                    . '<h4>' . htmlspecialchars($passage, ENT_QUOTES, 'UTF-8') . '</h4>'
                    . $result->text
                    . '</div>';

                if (empty($copyright) && !empty($result->copyright)) {
                    $copyright = $result->copyright;
                }
            }
        }

        if (empty($allText)) {
            return null;
        }

        return (object) [
            'text'      => implode("\n", $allText),
            'reference' => $reading,
            'copyright' => $copyright,
            'isHtml'    => true,
        ];
    }

    /**
     * Render a reading reference as an HTML scripture display.
     *
     * If lib_cwmscripture is available and has the translation installed,
     * renders inline scripture text. Otherwise returns the reference as plain text.
     *
     * @param   string  $reading   The human-readable reading reference
     * @param   string  $version   Bible translation code
     * @param   int     $mode      Display mode (0=hidden, 1=toggle, 2=visible)
     *
     * @return  string  HTML output
     *
     * @since   5.1.0
     */
    public static function renderReading(string $reading, string $version, int $mode = 2): string
    {
        if (!self::isLibraryAvailable()) {
            return self::buildBibleGatewayLink($reading, $version);
        }

        // Respect config display mode if mode not explicitly overridden
        if ($mode === 2) {
            $configMode = self::getScriptureDisplayMode();
            $mode       = match ($configMode) {
                'toggle' => 1,
                'link'   => 0,
                default  => 2,
            };
        }

        // Link-only mode — just show BibleGateway link, no scripture text
        if ($mode === 0) {
            return self::buildBibleGatewayLink($reading, $version);
        }

        $result = self::getPassageText($reading, $version);

        if ($result === null) {
            return self::buildBibleGatewayLink($reading, $version);
        }

        $html = '';

        // Toggle mode — collapsed by default with show/hide button
        if ($mode === 1) {
            $collapseId = 'scripture-' . md5($reading);
            $html .= '<div class="livingword-scripture-toggle mb-2">'
                . '<button class="btn btn-sm btn-outline-secondary" type="button"'
                . ' data-bs-toggle="collapse" data-bs-target="#' . $collapseId . '"'
                . ' aria-expanded="false" aria-controls="' . $collapseId . '">'
                . '<span class="icon-eye" aria-hidden="true"></span> '
                . htmlspecialchars('Show Scripture Text', ENT_QUOTES, 'UTF-8')
                . '</button></div>'
                . '<div class="collapse" id="' . $collapseId . '">';
        }

        $html .= '<div class="livingword-scripture-container"'
            . ' style="font-family: Georgia, \'Times New Roman\', serif; line-height: 1.8;">'
            . $result->text;

        if (!empty($result->copyright)) {
            $html .= '<p class="livingword-scripture-copyright text-muted small mt-2">'
                . htmlspecialchars($result->copyright, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        $html .= '</div>';

        if ($mode === 1) {
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Get the scripture display mode from component config.
     *
     * @return  string  'inline', 'toggle', or 'link'
     *
     * @since   5.7.0
     */
    public static function getScriptureDisplayMode(): string
    {
        $params = ComponentHelper::getParams('com_livingword');

        return $params->get('config_scripture_display', 'inline');
    }

    /**
     * Build a simple reading link — just the passage reference as clickable text.
     *
     * When lib_cwmscripture is not available, returns plain text.
     * When available, wraps in a container that can be enhanced with JS.
     *
     * @param   string  $reading  The human-readable reading reference
     * @param   string  $version  Bible translation code
     *
     * @return  string  HTML output
     *
     * @since   5.1.0
     */
    public static function buildReadingLink(string $reading, string $version): string
    {
        $url = self::getBibleGatewayUrl($reading, $version);

        return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener"'
            . ' class="livingword-reading-ref" data-version="'
            . htmlspecialchars($version, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($reading, ENT_QUOTES, 'UTF-8')
            . '</a>';
    }

    /**
     * Build a BibleGateway link for a passage reference.
     *
     * Used as fallback when lib_cwmscripture is not available or scripture
     * display mode is set to 'link'.
     *
     * @param   string  $reading  The human-readable reading reference
     * @param   string  $version  Bible translation code
     *
     * @return  string  HTML link to BibleGateway
     *
     * @since   5.7.0
     */
    public static function buildBibleGatewayLink(string $reading, string $version): string
    {
        $url = self::getBibleGatewayUrl($reading, $version);

        return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener"'
            . ' class="livingword-reading-ref livingword-external-link">'
            . htmlspecialchars($reading, ENT_QUOTES, 'UTF-8')
            . ' <span class="icon-out-2 small" aria-hidden="true"></span></a>';
    }

    /**
     * Get BibleGateway URL for a passage reference.
     *
     * @param   string  $reading  The passage reference
     * @param   string  $version  Bible version code
     *
     * @return  string  BibleGateway URL
     *
     * @since   5.7.0
     */
    public static function getBibleGatewayUrl(string $reading, string $version): string
    {
        $passage = urlencode($reading);
        $ver     = urlencode(strtoupper($version));

        return "https://www.biblegateway.com/passage/?search={$passage}&version={$ver}";
    }

    /**
     * Get audio data for a reading reference using Bible Brain.
     *
     * Parses the first passage from a semicolon-separated reading reference,
     * resolves the USFM book code and chapter, and returns audio URLs
     * via BibleBrainProvider.
     *
     * @param   string  $reading   The human-readable reading reference (e.g. "Genesis 1-3; Psalm 23")
     * @param   string  $version   Bible translation code (e.g. "kjv", "esv")
     *
     * @return  ?object  Object with audioUrl, book, chapter, verseTiming, copyright, or null
     *
     * @since   5.2.0
     */
    public static function getAudioForReading(string $reading, string $version): ?object
    {
        if (!self::isLibraryAvailable() || empty($reading)) {
            return null;
        }

        if (!class_exists('CWM\\Library\\Scripture\\Bible\\AudioProviderInterface')) {
            return null;
        }

        $params    = \CWM\Library\Scripture\Helper\ScriptureParamsHelper::getParams();
        $bbEnabled = (int) $params->get('provider_biblebrain', 0) === 1;
        $bbKey     = (string) $params->get('biblebrain_api_key', '');

        if (!$bbEnabled || empty($bbKey)) {
            return null;
        }

        $provider = \CWM\Library\Scripture\Bible\BibleProviderFactory::getProvider('biblebrain', $bbKey);

        if (!($provider instanceof \CWM\Library\Scripture\Bible\AudioProviderInterface)) {
            return null;
        }

        // Parse all passages to support multi-chapter audio
        $passages  = array_map('trim', explode(';', $reading));
        $audioList = [];
        $copyright = '';

        foreach ($passages as $passage) {
            if (empty($passage)) {
                continue;
            }

            $parsed = self::parsePassageReference($passage);

            if ($parsed === null) {
                continue;
            }

            // Handle chapter ranges (e.g. "Genesis 1-3" → chapters 1, 2, 3)
            $chapters = self::expandChapterRange($passage, $parsed['chapter']);

            foreach ($chapters as $chapter) {
                try {
                    $result = $provider->getAudio($parsed['book'], $chapter, $version);
                } catch (\Exception) {
                    continue;
                }

                if ($result !== null && $result->hasAudio()) {
                    $audioList[] = (object) [
                        'audioUrl'    => $result->getPrimaryUrl(),
                        'book'        => $result->book,
                        'chapter'     => $result->chapter,
                        'verseTiming' => $result->verseTiming,
                    ];

                    if (empty($copyright) && !empty($result->copyright)) {
                        $copyright = $result->copyright;
                    }
                }
            }
        }

        if (empty($audioList)) {
            return null;
        }

        return (object) [
            'audioUrl'    => $audioList[0]->audioUrl,
            'audioFiles'  => $audioList,
            'book'        => $audioList[0]->book,
            'chapter'     => $audioList[0]->chapter,
            'verseTiming' => $audioList[0]->verseTiming,
            'copyright'   => $copyright,
            'isPlaylist'  => \count($audioList) > 1,
        ];
    }

    /**
     * Check if audio Bible is available for the current configuration.
     *
     * @return  bool
     *
     * @since   5.2.0
     */
    public static function isAudioAvailable(): bool
    {
        if (!self::isLibraryAvailable()) {
            return false;
        }

        if (!class_exists('CWM\\Library\\Scripture\\Bible\\AudioProviderInterface')) {
            return false;
        }

        $params = \CWM\Library\Scripture\Helper\ScriptureParamsHelper::getParams();

        return (int) $params->get('provider_biblebrain', 0) === 1
            && !empty($params->get('biblebrain_api_key', ''));
    }

    /**
     * Expand a chapter range from a reference like "Genesis 1-3" into [1, 2, 3].
     *
     * @param   string  $reference    The full passage reference
     * @param   int     $startChapter The first chapter (already parsed)
     *
     * @return  array  Array of chapter numbers
     *
     * @since   5.7.0
     */
    private static function expandChapterRange(string $reference, int $startChapter): array
    {
        // Match patterns like "Genesis 1-3" or "Psalm 119-120"
        if (preg_match('/\s+(\d+)\s*-\s*(\d+)\s*$/', $reference, $m)) {
            $start = (int) $m[1];
            $end   = (int) $m[2];

            if ($end > $start && ($end - $start) < 50) {
                return range($start, $end);
            }
        }

        return [$startChapter];
    }

    /**
     * Parse a passage reference string into book USFM code and chapter.
     *
     * Handles formats like "Genesis 1", "Genesis 1-3", "John 3:16-18",
     * "1 Samuel 2:1-10".
     *
     * @param   string  $reference  Human-readable passage reference
     *
     * @return  ?array{book: string, chapter: int}  Parsed result or null
     *
     * @since   5.2.0
     */
    private static function parsePassageReference(string $reference): ?array
    {
        $ref = trim($reference);

        // Match "BookName Chapter" with optional verse range
        if (!preg_match('/^(.+?)\s+(\d+)(?:[:\-].*)?$/i', $ref, $m)) {
            return null;
        }

        $bookName = trim($m[1]);
        $chapter  = (int) $m[2];

        // Use BibleBrainProvider's USFM code mapping
        $usfmCodes = \CWM\Library\Scripture\Bible\Provider\BibleBrainProvider::getUsfmCodes();
        $bookNames = \CWM\Library\Scripture\Bible\AbstractBibleProvider::BOOK_NAMES ?? [];

        // Try reflection to access BOOK_NAMES constant
        try {
            $reflection = new \ReflectionClass(\CWM\Library\Scripture\Bible\AbstractBibleProvider::class);
            $bookNames  = $reflection->getConstant('BOOK_NAMES') ?: [];
        } catch (\ReflectionException) {
            return null;
        }

        $normalized = strtolower($bookName);

        foreach ($bookNames as $num => $name) {
            if (strtolower($name) === $normalized || str_starts_with(strtolower($name), $normalized)) {
                $usfmCode = $usfmCodes[$num] ?? '';

                if (!empty($usfmCode)) {
                    return ['book' => $usfmCode, 'chapter' => $chapter];
                }
            }
        }

        return null;
    }

    /**
     * Build passage text for email (plain HTML, no JS dependencies).
     *
     * @param   string  $reading  The human-readable reading reference
     * @param   string  $version  Bible translation code
     *
     * @return  string  HTML suitable for email body
     *
     * @since   5.1.0
     */
    public static function buildEmailContent(string $reading, string $version): string
    {
        $result = self::getPassageText($reading, $version);

        if ($result !== null) {
            return '<div style="font-family: Georgia, serif; line-height: 1.6;">'
                . '<p><strong>' . htmlspecialchars($reading, ENT_QUOTES, 'UTF-8') . '</strong></p>'
                . $result->text
                . (!empty($result->copyright) ? '<p style="font-size: 0.85em; color: #666;">' . htmlspecialchars($result->copyright, ENT_QUOTES, 'UTF-8') . '</p>' : '')
                . '</div>';
        }

        // Fallback: just the reference
        return '<p><strong>' . htmlspecialchars($reading, ENT_QUOTES, 'UTF-8') . '</strong></p>';
    }
}
