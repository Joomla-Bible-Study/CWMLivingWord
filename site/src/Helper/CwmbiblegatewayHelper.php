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
 * Helper for constructing BibleGateway.com URLs.
 *
 * This helper retains BibleGateway-specific URL mapping that doesn't belong
 * in the shared CWM Scripture library. Over time, this may be replaced by
 * a BibleGatewayProvider in lib_cwmscripture.
 *
 * @since  5.0.0
 */
class CwmbiblegatewayHelper
{
    /**
     * Base URL for BibleGateway passage lookups.
     *
     * @var string
     * @since 5.0.0
     */
    private const GATEWAY_BASE = 'https://www.biblegateway.com/passage/?search=';

    /**
     * Base URL for BibleGateway audio.
     *
     * @var string
     * @since 5.0.0
     */
    private const AUDIO_BASE = 'https://www.biblegateway.com/audio/';

    /**
     * Build a BibleGateway reading URL for the given passage and version.
     *
     * @param   string  $passage  The passage reference (e.g. "Genesis 1-3")
     * @param   string  $version  The BibleGateway version code (e.g. "NLT")
     *
     * @return  string  Full URL to BibleGateway passage
     *
     * @since   5.0.0
     */
    public static function buildReadingUrl(string $passage, string $version): string
    {
        return self::GATEWAY_BASE . urlencode($passage) . '&version=' . urlencode($version);
    }

    /**
     * Build a BibleGateway audio URL.
     *
     * @param   string  $audioVersion  The audio version identifier
     * @param   string  $book          Book number
     * @param   string  $chapter       Chapter number
     *
     * @return  string  Full URL to BibleGateway audio
     *
     * @since   5.0.0
     */
    public static function buildAudioUrl(string $audioVersion, string $book, string $chapter): string
    {
        return self::AUDIO_BASE . urlencode($audioVersion) . '/' . urlencode($book) . '/' . urlencode($chapter);
    }

    /**
     * Build a complete reading link (HTML anchor tag) for a daily reading.
     *
     * @param   string  $passage       The formatted passage text
     * @param   string  $version       Bible version code
     * @param   bool    $newWindow     Open in new window
     * @param   string  $cssClass      Optional CSS class
     *
     * @return  string  HTML anchor tag
     *
     * @since   5.0.0
     */
    public static function buildReadingLink(string $passage, string $version, bool $newWindow = true, string $cssClass = ''): string
    {
        $url    = self::buildReadingUrl($passage, $version);
        $target = $newWindow ? ' target="_blank" rel="noopener noreferrer"' : '';
        $class  = $cssClass !== '' ? ' class="' . htmlspecialchars($cssClass, ENT_QUOTES, 'UTF-8') . '"' : '';

        return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"' . $target . $class . '>'
            . htmlspecialchars($passage, ENT_QUOTES, 'UTF-8') . '</a>';
    }

    /**
     * Parse a stored reading reference string into human-readable passage names.
     *
     * Converts format like "LWBIBLEBOOK25 1-3;LWBIBLEBOOK50 12" into
     * "Lamentations 1-3; Philippians 12" using the book name mapping.
     *
     * @param   string  $reading   The raw reading reference string
     * @param   array   $bookMap   Mapping of book numbers to names
     *
     * @return  string  Human-readable passage reference
     *
     * @since   5.0.0
     */
    public static function parseReadingReference(string $reading, array $bookMap = []): string
    {
        if (empty($reading)) {
            return '';
        }

        $parts  = explode(';', $reading);
        $result = [];

        foreach ($parts as $part) {
            $part = trim($part);

            if (empty($part)) {
                continue;
            }

            // Match LWBIBLEBOOK<num> <chapters>
            if (preg_match('/LWBIBLEBOOK(\d+)\s+(.+)/', $part, $matches)) {
                $bookNum  = (int) $matches[1];
                $chapters = trim($matches[2]);
                $bookName = $bookMap[$bookNum] ?? 'Book ' . $bookNum;
                $result[] = $bookName . ' ' . $chapters;
            } else {
                $result[] = $part;
            }
        }

        return implode('; ', $result);
    }
}
