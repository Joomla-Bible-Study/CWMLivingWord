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
use Joomla\CMS\Language\Text;

/**
 * Scripture helper — bridges LivingWord with lib_cwmscripture.
 *
 * Provides passage text and audio retrieval through the CWM Scripture Library.
 * The library is a soft dependency: when it is missing, or cannot answer, a
 * reading is shown as its plain reference. LivingWord links to no external
 * Bible site.
 *
 * @since  5.1.0
 */
class CwmscriptureHelper
{
    /**
     * Highest chapter a plan reading can name. Psalms has 150.
     *
     * Bounds parsePassage(). An unbounded number cast to int saturates at
     * PHP_INT_MAX, and a chapter loop counting up to it never ends.
     *
     * @var    int
     * @since  __DEPLOY_VERSION__
     */
    private const int MAX_CHAPTER = 150;

    /**
     * Highest verse parsePassage() accepts. Psalm 119, the longest chapter, has 176.
     *
     * @var    int
     * @since  __DEPLOY_VERSION__
     */
    private const int MAX_VERSE = 200;

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
     * A reading holds one or more passages separated by semicolons, and each is
     * fetched on its own. A passage that can't be fetched keeps its heading, so a
     * day is never shown with one of its passages silently missing. Null only
     * when no passage at all returned text, so the caller can show the plain
     * reference instead.
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

        // Acquiring the provider is guarded for the same reason the fetch below
        // is: this component treats the library as optional and answers with
        // the plain reference when it cannot help, which a fatal here would
        // deny it the chance to do.
        try {
            $params   = \CWM\Library\Scripture\Helper\ScriptureParamsHelper::getParams();
            $provider = \CWM\Library\Scripture\Bible\BibleProviderFactory::getProviderForTranslation($version, $params);
        } catch (\Throwable) {
            return null;
        }

        $passages  = array_filter(array_map('trim', explode(';', $reading)), static fn (string $p): bool => $p !== '');
        $blocks    = [];
        $copyright = '';
        $anyText   = false;

        foreach ($passages as $passage) {
            $heading = '<h4>' . htmlspecialchars($passage, ENT_QUOTES, 'UTF-8') . '</h4>';
            $text    = self::fetchPassage($provider, $passage, $version, $copyright);

            if ($text === null) {
                $blocks[] = '<div class="scripture-passage scripture-passage-unavailable">' . $heading . '</div>';

                continue;
            }

            $anyText  = true;
            $blocks[] = '<div class="scripture-passage">' . $heading . $text . '</div>';
        }

        if (!$anyText) {
            return null;
        }

        return (object) [
            'text'      => implode("\n", $blocks),
            'reference' => $reading,
            'copyright' => $copyright,
            'isHtml'    => true,
        ];
    }

    /**
     * Fetch the text of one passage, or null when any part of it is unavailable.
     *
     * The passage is split by parsePassage() and every part is asked for as a
     * structured reference through getPassageFor(), the way Proclaim calls the
     * library. Passing plan strings through getPassage() instead left each
     * provider to read them in its own dialect. The library's own parser
     * deliberately doesn't accept a verse-less chapter range like "Genesis 1-3",
     * and LocalProvider read that as Genesis 1:1-3. A passage parsePassage()
     * can't split still goes to getPassage() as written, so a custom plan's
     * reading that worked before keeps working.
     *
     * All or nothing per passage: showing "Romans 5-8" under the heading
     * "Romans 5-8,12" would misquote the reading.
     *
     * @param   object  $provider   A lib_cwmscripture Bible provider
     * @param   string  $passage    One passage, e.g. "Romans 4:1-5,9-25"
     * @param   string  $version    Bible translation code
     * @param   string  $copyright  Set to the first copyright notice a part returns
     *
     * @return  ?string  Passage HTML, or null when it could not be fetched in full
     *
     * @since   __DEPLOY_VERSION__
     */
    private static function fetchPassage(object $provider, string $passage, string $version, string &$copyright): ?string
    {
        $parts = self::parsePassage($passage);

        if ($parts === []) {
            $result = self::fetchString($provider, $passage, $version);

            if ($result === null) {
                return null;
            }

            $copyright = $copyright !== '' ? $copyright : (string) $result->copyright;

            return $result->text;
        }

        $texts = [];

        foreach ($parts as $part) {
            $result = self::fetchPart($provider, $part, $version);

            if ($result === null) {
                return null;
            }

            // Disjoint parts ("Romans 5-8,12") get a label each, or the reader
            // can't tell where one stops and the next starts.
            $label = \count($parts) > 1
                ? '<p class="scripture-part"><strong>' . htmlspecialchars(self::formatPart($part), ENT_QUOTES, 'UTF-8') . '</strong></p>'
                : '';

            $texts[]   = $label . $result->text;
            $copyright = $copyright !== '' ? $copyright : (string) $result->copyright;
        }

        return implode("\n", $texts);
    }

    /**
     * Ask the provider for one part as a structured reference.
     *
     * @param   object                                                                                    $provider  Bible provider
     * @param   array{book: string, chapterBegin: int, verseBegin: int, chapterEnd: int, verseEnd: int}  $part      One part
     * @param   string                                                                                    $version   Bible translation code
     *
     * @return  ?object  The provider's result when it has text, otherwise null
     *
     * @since   __DEPLOY_VERSION__
     */
    private static function fetchPart(object $provider, array $part, string $version): ?object
    {
        try {
            $book = \CWM\Library\Scripture\Helper\ScriptureHelper::getBookNumber($part['book']);

            if ($book === 0) {
                return null;
            }

            // Libraries before 1.1.13 have no structured entry point; ask for the
            // rendered part as a string there, as Proclaim's fetchPassage() does.
            if (!method_exists($provider, 'getPassageFor')) {
                return self::fetchString($provider, self::formatPart($part), $version);
            }

            $result = $provider->getPassageFor(
                new \CWM\Library\Scripture\Helper\ScriptureReference(
                    booknumber: $book,
                    chapterBegin: $part['chapterBegin'],
                    verseBegin: $part['verseBegin'],
                    chapterEnd: $part['chapterEnd'],
                    verseEnd: $part['verseEnd'],
                    referenceText: self::formatPart($part),
                ),
                $version
            );
        } catch (\Throwable) {
            // Throwable, not Exception. A fault inside the library is an
            // Error as often as an Exception — older lib_cwmscripture
            // releases called $this->getDatabase() from the cache read
            // before any provider defined it, so every cached lookup raised
            // "Call to undefined method". Catching Exception alone let that
            // escape and take the whole reading page down with a 500, in a
            // component whose every call site is written to fall back to
            // the plain reference when the library cannot answer.
            return null;
        }

        return $result !== null && $result->hasText() ? $result : null;
    }

    /**
     * Ask the provider for a reference string, as written.
     *
     * @param   object  $provider   Bible provider
     * @param   string  $reference  Reference string
     * @param   string  $version    Bible translation code
     *
     * @return  ?object  The provider's result when it has text, otherwise null
     *
     * @since   __DEPLOY_VERSION__
     */
    private static function fetchString(object $provider, string $reference, string $version): ?object
    {
        try {
            $result = $provider->getPassage($reference, $version);
        } catch (\Throwable) {
            // See fetchPart(): a library fault must degrade to "no text".
            return null;
        }

        return $result !== null && $result->hasText() ? $result : null;
    }

    /**
     * Split one plan passage into the plain references it names.
     *
     * Plan readings use citation shorthand that no lib_cwmscripture parser
     * accepts, and none should have to. That means verse-less chapter ranges
     * ("Ecclesiastes 1-3") and comma lists ("Romans 5-8,12",
     * "Romans 4:1-5,9-25", "Genesis 16:1-15,18:1-15,21:1-7").
     *
     * A comma continues in the current mode. After a chapter-level item, a
     * number is a chapter. After a "chapter:verse" item, it is a verse in that
     * chapter, until another colon names a new chapter. A verse of 0 means
     * whole chapters, matching ScriptureReference.
     *
     * Pure: no library calls, so it can be tested without lib_cwmscripture.
     *
     * @param   string  $passage  One passage, e.g. "Romans 4:1-5,9-25"
     *
     * @return  list<array{book: string, chapterBegin: int, verseBegin: int, chapterEnd: int, verseEnd: int}>
     *          Empty when the passage is not in a form this understands
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function parsePassage(string $passage): array
    {
        $passage = trim((string) preg_replace('/\s+/', ' ', $passage));

        if (!preg_match('/^(.+?)\s+(\d[\d:,\-\s]*)$/', $passage, $m)) {
            return [];
        }

        $book    = trim($m[1]);
        $spec    = (string) preg_replace('/\s+/', '', $m[2]);
        $parts   = [];
        $chapter = 0; // The chapter verses belong to; 0 while in chapter mode.

        // At most three digits per number, so no number can come near
        // PHP_INT_MAX; MAX_CHAPTER and MAX_VERSE then bound them properly.
        foreach (explode(',', $spec) as $item) {
            if (preg_match('/^(\d{1,3}):(\d{1,3})-(\d{1,3}):(\d{1,3})$/', $item, $n)) {
                $part    = [(int) $n[1], (int) $n[2], (int) $n[3], (int) $n[4]];
                $chapter = $part[2];
            } elseif (preg_match('/^(\d{1,3}):(\d{1,3})(?:-(\d{1,3}))?$/', $item, $n)) {
                $part    = [(int) $n[1], (int) $n[2], (int) $n[1], (int) ($n[3] ?? $n[2])];
                $chapter = $part[0];
            } elseif (preg_match('/^(\d{1,3})(?:-(\d{1,3}))?$/', $item, $n)) {
                $from = (int) $n[1];
                $to   = (int) ($n[2] ?? $n[1]);
                $part = $chapter > 0 ? [$chapter, $from, $chapter, $to] : [$from, 0, $to, 0];
            } else {
                return [];
            }

            [$chapterBegin, $verseBegin, $chapterEnd, $verseEnd] = $part;

            $isVerse = $verseBegin > 0;

            if (
                $chapterBegin < 1
                || $chapterEnd < $chapterBegin
                || $chapterEnd > self::MAX_CHAPTER
                || $verseBegin > self::MAX_VERSE
                || $verseEnd > self::MAX_VERSE
                || ($isVerse && ($verseEnd < 1 || ($chapterEnd === $chapterBegin && $verseEnd < $verseBegin)))
            ) {
                return [];
            }

            $parts[] = [
                'book'         => $book,
                'chapterBegin' => $chapterBegin,
                'verseBegin'   => $verseBegin,
                'chapterEnd'   => $chapterEnd,
                'verseEnd'     => $verseEnd,
            ];
        }

        return $parts;
    }

    /**
     * Render one parsed part back as a reference, e.g. "Romans 4:9-25".
     *
     * @param   array{book: string, chapterBegin: int, verseBegin: int, chapterEnd: int, verseEnd: int}  $part  One part
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function formatPart(array $part): string
    {
        $out = $part['book'] . ' ' . $part['chapterBegin'];

        if ($part['verseBegin'] === 0) {
            return $part['chapterEnd'] > $part['chapterBegin'] ? $out . '-' . $part['chapterEnd'] : $out;
        }

        $out .= ':' . $part['verseBegin'];

        if ($part['chapterEnd'] > $part['chapterBegin']) {
            return $out . '-' . $part['chapterEnd'] . ':' . $part['verseEnd'];
        }

        return $part['verseEnd'] > $part['verseBegin'] ? $out . '-' . $part['verseEnd'] : $out;
    }

    /**
     * Render a reading reference as an HTML scripture display.
     *
     * If lib_cwmscripture is available and has the translation installed,
     * renders inline scripture text. Otherwise returns the reference as plain text.
     *
     * @param   string  $reading   The human-readable reading reference
     * @param   string  $version   Bible translation code
     * @param   int     $mode      Display mode (0=reference only, 1=toggle, 2=visible)
     *
     * @return  string  HTML output
     *
     * @since   5.1.0
     */
    public static function renderReading(string $reading, string $version, int $mode = 2): string
    {
        if (!self::isLibraryAvailable()) {
            return self::buildReadingReference($reading, $version);
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

        // Reference-only mode — the reference, no scripture text
        if ($mode === 0) {
            return self::buildReadingReference($reading, $version);
        }

        $result = self::getPassageText($reading, $version);

        if ($result === null) {
            return self::buildReadingReference($reading, $version);
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
                . htmlspecialchars(Text::_('COM_LIVINGWORD_SCRIPTURE_SHOW_TEXT'), ENT_QUOTES, 'UTF-8')
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
     * Render a reading reference as plain text.
     *
     * What a reader sees wherever no scripture text is shown: the library is
     * missing, the display mode is "reference only", or no provider could
     * answer. Deliberately not a link — LivingWord sends readers to no external
     * Bible site. `data-version` stays on the element so a script can enhance
     * the reference in place.
     *
     * @param   string  $reading  The human-readable reading reference
     * @param   string  $version  Bible translation code
     *
     * @return  string  HTML output
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function buildReadingReference(string $reading, string $version): string
    {
        return '<span class="livingword-reading-ref" data-version="'
            . htmlspecialchars($version, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($reading, ENT_QUOTES, 'UTF-8')
            . '</span>';
    }

    /**
     * Render a reading reference.
     *
     * @param   string  $reading  The human-readable reading reference
     * @param   string  $version  Bible translation code
     *
     * @return  string  HTML output
     *
     * @since       5.1.0
     *
     * @deprecated  __DEPLOY_VERSION__  will be removed in 6.0.0
     *              Use buildReadingReference() instead. This used to link the
     *              reference to an external Bible site; it now returns the same
     *              plain reference, and stays only so site template overrides
     *              that still call it keep rendering.
     */
    public static function buildReadingLink(string $reading, string $version): string
    {
        return self::buildReadingReference($reading, $version);
    }

    /**
     * Get audio data for a reading reference using Bible Brain.
     *
     * Every passage in the semicolon-separated reading is split by
     * parsePassage(), and every chapter it covers is fetched. A comma list like
     * "Romans 5-8,12" plays chapters 5 to 8 and 12, and "2 Timothy 3:10-4:22"
     * plays chapters 3 and 4. Returns audio URLs via BibleBrainProvider.
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

        try {
            $params    = \CWM\Library\Scripture\Helper\ScriptureParamsHelper::getParams();
            $bbEnabled = (int) $params->get('provider_biblebrain', 0) === 1;
            $bbKey     = (string) $params->get('biblebrain_api_key', '');

            if (!$bbEnabled || empty($bbKey)) {
                return null;
            }

            $provider = \CWM\Library\Scripture\Bible\BibleProviderFactory::getProvider('biblebrain', $bbKey);
        } catch (\Throwable) {
            return null;
        }

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

            foreach (self::audioTargets($passage) as $target) {
                try {
                    $result = $provider->getAudio($target['book'], $target['chapter'], $version);
                } catch (\Throwable) {
                    // Throwable for the same reason as the passage fetch above:
                    // a library-side Error must degrade to "no audio", never to
                    // a fatal.
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

        try {
            $params = \CWM\Library\Scripture\Helper\ScriptureParamsHelper::getParams();

            return (int) $params->get('provider_biblebrain', 0) === 1
                && !empty($params->get('biblebrain_api_key', ''));
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * The book codes and chapters to fetch audio for, for one passage.
     *
     * @param   string  $passage  One passage, e.g. "Romans 5-8,12"
     *
     * @return  list<array{book: string, chapter: int}>  USFM book code and chapter
     *
     * @since   __DEPLOY_VERSION__
     */
    private static function audioTargets(string $passage): array
    {
        $targets = [];

        foreach (self::audioChapters(self::parsePassage($passage)) as $item) {
            $code = \CWM\Library\Scripture\Bible\AbstractBibleProvider::resolveBookCode($item['book']);

            if ($code !== '') {
                $targets[] = ['book' => $code, 'chapter' => $item['chapter']];
            }
        }

        if ($targets === []) {
            // A form parsePassage() can't split gets its first chapter, as
            // every passage did before.
            $parsed = self::parsePassageReference($passage);

            if ($parsed !== null) {
                $targets[] = $parsed;
            }
        }

        return $targets;
    }

    /**
     * The chapters a passage's parts cover, in reading order, each once.
     *
     * "Romans 4:1-5,9-25" covers chapter 4 twice and yields it once. A part
     * spanning 50 chapters or more yields only its first chapter, as the range
     * expansion this replaces did, so a malformed plan can't queue a whole
     * book of audio.
     *
     * Pure: no library calls.
     *
     * @param   list<array{book: string, chapterBegin: int, verseBegin: int, chapterEnd: int, verseEnd: int}>  $parts  Parts from parsePassage()
     *
     * @return  list<array{book: string, chapter: int}>
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function audioChapters(array $parts): array
    {
        $chapters = [];
        $seen     = [];

        foreach ($parts as $part) {
            $span  = $part['chapterEnd'] - $part['chapterBegin'];
            $count = $span >= 50 ? 1 : $span + 1;

            // Counted, not compared against chapterEnd: this is public and takes
            // any array, and a chapter number saturated at PHP_INT_MAX would turn
            // "$chapter <= $last" into a loop that never ends.
            for ($offset = 0; $offset < $count; $offset++) {
                $chapter = $part['chapterBegin'] + $offset;
                $key     = $part['book'] . '|' . $chapter;

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $chapters[] = ['book' => $part['book'], 'chapter' => $chapter];
            }
        }

        return $chapters;
    }

    /**
     * Parse a passage reference string into book USFM code and first chapter.
     *
     * The fallback for passages parsePassage() can't split. It handles formats
     * like "Genesis 1", "John 3:16-18" and "1 Samuel 2:1-10".
     *
     * Book resolution is the library's job, via the public
     * `AbstractBibleProvider::resolveBookCode()`. This used to walk
     * `AbstractBibleProvider::BOOK_NAMES` by reflection, which read a
     * `protected` constant the library never promised to keep — lib 1.1.13
     * moves it into `Book\BookCodes`, at which point `getConstant()` returns
     * false, the name table is empty, and every lookup silently returns null
     * (#118). `resolveBookCode()` also resolves translated names and
     * abbreviations, and returns '' rather than guessing when an abbreviation
     * prefixes more than one book — "Jo" no longer answers Joshua for John.
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

        $usfmCode = \CWM\Library\Scripture\Bible\AbstractBibleProvider::resolveBookCode($bookName);

        if ($usfmCode === '') {
            return null;
        }

        return ['book' => $usfmCode, 'chapter' => $chapter];
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
