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

        $passages = array_map('trim', explode(';', $reading));
        $allText  = [];
        $copyright = '';

        foreach ($passages as $passage) {
            if (empty($passage)) {
                continue;
            }

            $result = $provider->getPassage($passage, $version);

            if ($result->hasText()) {
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
            return '<span class="livingword-reading-ref">'
                . htmlspecialchars($reading, ENT_QUOTES, 'UTF-8')
                . '</span>';
        }

        $result = self::getPassageText($reading, $version);

        if ($result === null) {
            return '<span class="livingword-reading-ref">'
                . htmlspecialchars($reading, ENT_QUOTES, 'UTF-8')
                . '</span>';
        }

        $renderer = new \CWM\Library\Scripture\Renderer\ScriptureRenderer();
        $passageResult = new \CWM\Library\Scripture\Bible\BiblePassageResult(
            text: $result->text,
            reference: $result->reference,
            translation: $version,
            copyright: $result->copyright,
            isHtml: true
        );

        return $renderer->renderTextPassage($passageResult, $mode);
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
        return '<span class="livingword-reading-ref" data-version="'
            . htmlspecialchars($version, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($reading, ENT_QUOTES, 'UTF-8')
            . '</span>';
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

        $params   = \CWM\Library\Scripture\Helper\ScriptureParamsHelper::getParams();
        $bbEnabled = (int) $params->get('provider_biblebrain', 0) === 1;
        $bbKey     = (string) $params->get('biblebrain_api_key', '');

        if (!$bbEnabled || empty($bbKey)) {
            return null;
        }

        $provider = \CWM\Library\Scripture\Bible\BibleProviderFactory::getProvider('biblebrain', $bbKey);

        if (!($provider instanceof \CWM\Library\Scripture\Bible\AudioProviderInterface)) {
            return null;
        }

        // Parse the first passage to get book and chapter
        $passages = array_map('trim', explode(';', $reading));
        $firstPassage = $passages[0] ?? '';

        if (empty($firstPassage)) {
            return null;
        }

        $parsed = self::parsePassageReference($firstPassage);

        if ($parsed === null) {
            return null;
        }

        $result = $provider->getAudio($parsed['book'], $parsed['chapter'], $version);

        if (!$result->hasAudio()) {
            return null;
        }

        return (object) [
            'audioUrl'    => $result->getPrimaryUrl(),
            'audioFiles'  => $result->audioFiles,
            'book'        => $result->book,
            'chapter'     => $result->chapter,
            'verseTiming' => $result->verseTiming,
            'copyright'   => $result->copyright,
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
