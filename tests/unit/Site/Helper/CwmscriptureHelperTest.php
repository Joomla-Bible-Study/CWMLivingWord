<?php

/**
 * @package    Livingword.Tests
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Tests\Site\Helper;

use CWM\Component\Livingword\Site\Helper\CwmscriptureHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for scripture helper reference rendering and parsing.
 *
 * Tests only pure methods that don't require lib_cwmscripture or Joomla.
 *
 * @since  5.7.0
 */
class CwmscriptureHelperTest extends TestCase
{
    // ── buildReadingReference ──

    public function testBuildReadingReferenceContainsReference(): void
    {
        $html = CwmscriptureHelper::buildReadingReference('Genesis 1-3', 'kjv');

        $this->assertStringContainsString('Genesis 1-3', $html);
    }

    public function testBuildReadingReferenceIsNotALink(): void
    {
        // LivingWord links to no external Bible site: a reference is text,
        // never a link, whatever the version.
        $html = CwmscriptureHelper::buildReadingReference('Psalm 23', 'niv');

        $this->assertStringStartsWith('<span class="livingword-reading-ref"', $html);
        $this->assertStringNotContainsString('<a ', $html);
        $this->assertStringNotContainsString('href=', $html);
        $this->assertStringNotContainsString('target=', $html);
        $this->assertStringNotContainsStringIgnoringCase('biblegateway', $html);
    }

    public function testBuildReadingReferenceHasDataVersion(): void
    {
        $html = CwmscriptureHelper::buildReadingReference('Genesis 1', 'web');

        $this->assertStringContainsString('data-version="web"', $html);
    }

    public function testBuildReadingReferenceEscapesHtml(): void
    {
        $html = CwmscriptureHelper::buildReadingReference('Test <script>alert(1)</script>', 'kjv');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testBuildReadingReferenceEscapesVersionAttribute(): void
    {
        $html = CwmscriptureHelper::buildReadingReference('Genesis 1', 'kjv" onmouseover="x');

        $this->assertStringNotContainsString('" onmouseover=', $html);
        $this->assertStringContainsString('&quot;', $html);
    }

    // ── buildReadingLink (deprecated alias kept for template overrides) ──

    public function testBuildReadingLinkMatchesBuildReadingReference(): void
    {
        $this->assertSame(
            CwmscriptureHelper::buildReadingReference('John 3:16', 'kjv'),
            CwmscriptureHelper::buildReadingLink('John 3:16', 'kjv')
        );
    }

    // ── isLibraryAvailable (should return false in test env) ──

    public function testIsLibraryAvailableReturnsFalseWithoutLibrary(): void
    {
        $this->assertFalse(CwmscriptureHelper::isLibraryAvailable());
    }

    // ── isAudioAvailable (should return false in test env) ──

    public function testIsAudioAvailableReturnsFalseWithoutLibrary(): void
    {
        $this->assertFalse(CwmscriptureHelper::isAudioAvailable());
    }

    // ── renderReading fallback (without lib_cwmscripture) ──

    public function testRenderReadingFallsBackToPlainReference(): void
    {
        $html = CwmscriptureHelper::renderReading('Genesis 1-3', 'kjv');

        $this->assertSame(CwmscriptureHelper::buildReadingReference('Genesis 1-3', 'kjv'), $html);
        $this->assertStringNotContainsString('href=', $html);
        $this->assertStringNotContainsStringIgnoringCase('biblegateway', $html);
    }

    public function testHelperHasNoBibleGatewayApi(): void
    {
        // BibleGateway is gone for good: the helper must not grow a way back.
        $this->assertFalse(method_exists(CwmscriptureHelper::class, 'getBibleGatewayUrl'));
        $this->assertFalse(method_exists(CwmscriptureHelper::class, 'buildBibleGatewayLink'));
    }

    // ── parsePassage ──

    /**
     * Every citation shape the shipped plans use, with what it means.
     *
     * Each part is [book, chapterBegin, verseBegin, chapterEnd, verseEnd]; a
     * verse of 0 means whole chapters, as in lib_cwmscripture's ScriptureReference.
     *
     * @return array<string, array{0: string, 1: list<array{0: string, 1: int, 2: int, 3: int, 4: int}>}>
     */
    public static function passageProvider(): array
    {
        return [
            'whole chapter'            => ['John 12', [['John', 12, 0, 12, 0]]],
            'chapter range'            => ['Ecclesiastes 1-3', [['Ecclesiastes', 1, 0, 3, 0]]],
            'single verse'             => ['John 3:16', [['John', 3, 16, 3, 16]]],
            'verse range'              => ['Acts 13:1-13', [['Acts', 13, 1, 13, 13]]],
            'cross-chapter range'      => ['2 Timothy 3:10-4:22', [['2 Timothy', 3, 10, 4, 22]]],
            'numbered book'            => ['1 John 2', [['1 John', 2, 0, 2, 0]]],
            'multi-word book'          => ['Song of Solomon 1-3', [['Song of Solomon', 1, 0, 3, 0]]],
            'chapter list'             => ['Philippians 2,4', [['Philippians', 2, 0, 2, 0], ['Philippians', 4, 0, 4, 0]]],
            'chapter ranges listed'    => ['Revelation 1-4,19-22', [['Revelation', 1, 0, 4, 0], ['Revelation', 19, 0, 22, 0]]],
            'mixed chapter list'       => ['Romans 5-8,12', [['Romans', 5, 0, 8, 0], ['Romans', 12, 0, 12, 0]]],
            'verses after a colon'     => ['Romans 4:1-5,9-25', [['Romans', 4, 1, 4, 5], ['Romans', 4, 9, 4, 25]]],
            'colon resets the chapter' => [
                'Genesis 16:1-15,18:1-15,21:1-7',
                [['Genesis', 16, 1, 16, 15], ['Genesis', 18, 1, 18, 15], ['Genesis', 21, 1, 21, 7]],
            ],
            'chapter then verses'     => ['Genesis 3,4:1-10', [['Genesis', 3, 0, 3, 0], ['Genesis', 4, 1, 4, 10]]],
            'cross-chapter then more' => [
                'Luke 1:1-2:20,3:1-6',
                [['Luke', 1, 1, 2, 20], ['Luke', 3, 1, 3, 6]],
            ],
            'verses continue after a cross-chapter range' => [
                'Luke 1:5-2:4,6-7',
                [['Luke', 1, 5, 2, 4], ['Luke', 2, 6, 2, 7]],
            ],
            'spaces are tolerated' => ['  Genesis   1 - 3 ', [['Genesis', 1, 0, 3, 0]]],
        ];
    }

    #[DataProvider('passageProvider')]
    public function testParsePassage(string $passage, array $expected): void
    {
        $parts = array_map(
            static fn (array $p): array => [$p['book'], $p['chapterBegin'], $p['verseBegin'], $p['chapterEnd'], $p['verseEnd']],
            CwmscriptureHelper::parsePassage($passage)
        );

        $this->assertSame($expected, $parts);
    }

    public function testParsePassageRejectsWhatItCannotRead(): void
    {
        // Rejecting is safe: fetchPassage() then hands the string to the
        // provider as written, which is what happened to every passage before.
        foreach (['', 'Genesis', 'Genesis 0', 'Genesis 3-1', 'Genesis 2:5-3', 'Genesis 1:9-4', 'Genesis 1:a', 'Genesis 1,,2', 'Genesis 1–3'] as $passage) {
            $this->assertSame([], CwmscriptureHelper::parsePassage($passage), "\"$passage\" must not parse");
        }
    }

    public function testParsePassageBoundsNumbers(): void
    {
        // A number cast to int saturates at PHP_INT_MAX, and a chapter loop
        // counting up to it never ends. Bounding the parser keeps such a number
        // out of every loop downstream.
        foreach (['Genesis 99999999999999999999', 'Genesis 1000', 'Psalm 151', 'Psalm 119:201', 'Genesis 1-151'] as $passage) {
            $this->assertSame([], CwmscriptureHelper::parsePassage($passage), "\"$passage\" must not parse");
        }

        // The real maxima still parse: Psalms has 150 chapters, Psalm 119 has 176 verses.
        $this->assertCount(1, CwmscriptureHelper::parsePassage('Psalm 150'));
        $this->assertCount(1, CwmscriptureHelper::parsePassage('Psalm 119:176'));
    }

    public function testAudioChaptersTerminatesOnSaturatedNumbers(): void
    {
        // audioChapters() is public and accepts any parts, not only parsePassage()
        // output. Counting up to PHP_INT_MAX with "<=" would never end: the
        // increment overflows to a float that still compares equal.
        $chapters = CwmscriptureHelper::audioChapters([[
            'book'         => 'Genesis',
            'chapterBegin' => PHP_INT_MAX - 1,
            'verseBegin'   => 0,
            'chapterEnd'   => PHP_INT_MAX,
            'verseEnd'     => 0,
        ]]);

        $this->assertCount(2, $chapters);
    }

    public function testEveryShippedPassageParses(): void
    {
        // The plans ship 2,600-odd passages, 119 of them comma lists in a dozen
        // shapes. Before parsePassage() each went to the scripture library as a
        // raw string; none of the library's parsers reads a comma list, and they
        // disagree on "Genesis 1-3". This keeps every shipped passage on the
        // structured path.
        $sql = file_get_contents(\dirname(__DIR__, 4) . '/admin/sql/install.mysql.utf8.sql');

        preg_match_all("/^\(\d+, \d+, '([^']*)'/m", (string) $sql, $rows);

        $passages = [];

        foreach ($rows[1] as $reading) {
            foreach (explode(';', $reading) as $passage) {
                if (trim($passage) !== '') {
                    $passages[] = trim($passage);
                }
            }
        }

        $this->assertGreaterThan(2500, \count($passages), 'the plan seed must have been read');

        $unparsed = array_values(array_filter($passages, static fn (string $p): bool => CwmscriptureHelper::parsePassage($p) === []));

        $this->assertSame([], $unparsed, 'every shipped passage must parse');
    }

    // ── formatPart ──

    public function testFormatPartRoundTrips(): void
    {
        foreach (['John 12', 'Ecclesiastes 1-3', 'John 3:16', 'Acts 13:1-13', '2 Timothy 3:10-4:22'] as $passage) {
            $parts = CwmscriptureHelper::parsePassage($passage);

            $this->assertCount(1, $parts);
            $this->assertSame($passage, CwmscriptureHelper::formatPart($parts[0]));
        }
    }

    public function testFormatPartLabelsEachListedPart(): void
    {
        $labels = array_map(
            [CwmscriptureHelper::class, 'formatPart'],
            CwmscriptureHelper::parsePassage('Romans 4:1-5,9-25')
        );

        $this->assertSame(['Romans 4:1-5', 'Romans 4:9-25'], $labels);
    }

    // ── audioChapters ──

    /**
     * @return array<string, array{0: string, 1: list<int>}>
     */
    public static function audioProvider(): array
    {
        return [
            'chapter range'         => ['Genesis 1-3', [1, 2, 3]],
            'verse range'           => ['John 3:16-18', [3]],
            'cross-chapter range'   => ['2 Timothy 3:10-4:22', [3, 4]],
            'chapter list'          => ['Romans 5-8,12', [5, 6, 7, 8, 12]],
            'verses in one chapter' => ['Romans 4:1-5,9-25', [4]],
            'colon list'            => ['Genesis 16:1-15,18:1-15,21:1-7', [16, 18, 21]],
            'span of 50 or more'    => ['Psalm 1-150', [1]],
        ];
    }

    #[DataProvider('audioProvider')]
    public function testAudioChapters(string $passage, array $expected): void
    {
        $chapters = array_column(
            CwmscriptureHelper::audioChapters(CwmscriptureHelper::parsePassage($passage)),
            'chapter'
        );

        $this->assertSame($expected, $chapters);
    }
}
