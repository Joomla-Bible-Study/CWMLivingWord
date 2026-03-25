<?php

/**
 * @package    Livingword.Tests
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Tests\Site\Helper;

use CWM\Component\Livingword\Site\Helper\CwmprogressHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for passage counting and splitting logic.
 *
 * @since  5.5.0
 */
class CwmprogressHelperTest extends TestCase
{
    // ── countPassages ──

    public function testCountPassagesSingle(): void
    {
        $this->assertSame(1, CwmprogressHelper::countPassages('Genesis 1-3'));
    }

    public function testCountPassagesMultiple(): void
    {
        $this->assertSame(3, CwmprogressHelper::countPassages('Genesis 1-3; Psalm 1; Matthew 1'));
    }

    public function testCountPassagesTwo(): void
    {
        $this->assertSame(2, CwmprogressHelper::countPassages('Genesis 1; Psalm 23'));
    }

    public function testCountPassagesEmptyString(): void
    {
        $this->assertSame(1, CwmprogressHelper::countPassages(''));
    }

    public function testCountPassagesWhitespaceOnly(): void
    {
        $this->assertSame(1, CwmprogressHelper::countPassages('   '));
    }

    public function testCountPassagesTrailingSemicolon(): void
    {
        // "Genesis 1;" → after split and filter, 1 passage
        $this->assertSame(1, CwmprogressHelper::countPassages('Genesis 1;'));
    }

    public function testCountPassagesMultipleSemicolons(): void
    {
        // "Genesis 1;;Psalm 23" → after filter, 2 passages
        $this->assertSame(2, CwmprogressHelper::countPassages('Genesis 1;;Psalm 23'));
    }

    // ── splitPassages ──

    public function testSplitPassagesSingle(): void
    {
        $result = CwmprogressHelper::splitPassages('Genesis 1-3');

        $this->assertSame(['Genesis 1-3'], $result);
    }

    public function testSplitPassagesMultiple(): void
    {
        $result = CwmprogressHelper::splitPassages('Genesis 1-3; Psalm 1; Matthew 1');

        $this->assertSame(['Genesis 1-3', 'Psalm 1', 'Matthew 1'], $result);
    }

    public function testSplitPassagesTrimsWhitespace(): void
    {
        $result = CwmprogressHelper::splitPassages('  Genesis 1  ;  Psalm 23  ');

        $this->assertSame(['Genesis 1', 'Psalm 23'], $result);
    }

    public function testSplitPassagesEmpty(): void
    {
        $result = CwmprogressHelper::splitPassages('');

        $this->assertSame([''], $result);
    }

    public function testSplitPassagesFiltersEmptySegments(): void
    {
        $result = CwmprogressHelper::splitPassages('Genesis 1;;Psalm 23');

        $this->assertSame(['Genesis 1', 'Psalm 23'], $result);
    }

    // ── countPassages + splitPassages consistency ──

    #[DataProvider('passageProvider')]
    public function testCountMatchesSplitLength(string $reading, int $expectedCount): void
    {
        $this->assertSame($expectedCount, CwmprogressHelper::countPassages($reading));
        $this->assertCount($expectedCount, CwmprogressHelper::splitPassages($reading));
    }

    public static function passageProvider(): array
    {
        return [
            'single'         => ['Genesis 1-3', 1],
            'two passages'   => ['Genesis 1; Exodus 1', 2],
            'three passages' => ['Genesis 1; Psalm 23; John 3:16', 3],
            'five passages'  => ['Gen 1; Gen 2; Gen 3; Gen 4; Gen 5', 5],
        ];
    }
}
