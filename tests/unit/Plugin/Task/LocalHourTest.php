<?php

/**
 * @package    Livingword.Tests
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Tests\Plugin\Task;

use CWM\Plugin\Task\Livingword\Support\LocalHour;
use PHPUnit\Framework\TestCase;

/**
 * The rule that decides whose daily email is due.
 *
 * Written after finding that the routine compared a reader's preferred hour
 * against date('G') — UTC, because Joomla pins PHP there — while the timezone
 * stored on the same row was read by nothing. A reader in Chicago asking for
 * 6am was mailed at 1am.
 *
 * Every case here is a fixed instant, so the suite gives the same answer at
 * 3am in June as at noon in December.
 *
 * @since  __DEPLOY_VERSION__
 */
class LocalHourTest extends TestCase
{
    /**
     * 12:00 UTC on a summer day and on a winter day.
     */
    private const SUMMER = '2026-07-15 12:00:00';
    private const WINTER = '2026-01-15 12:00:00';

    private function at(string $utc): \DateTimeImmutable
    {
        return new \DateTimeImmutable($utc, new \DateTimeZone('UTC'));
    }

    public function testUtcIsTheHourItself(): void
    {
        $this->assertSame(12, LocalHour::inZone('UTC', 'UTC', $this->at(self::SUMMER)));
    }

    public function testAZoneBehindUtcReadsEarlier(): void
    {
        // Chicago is UTC-5 in July: noon UTC is 7am there. Under the old rule
        // this reader's "7am" never matched, and their 6am mail arrived at 1am.
        $this->assertSame(7, LocalHour::inZone('America/Chicago', 'UTC', $this->at(self::SUMMER)));
    }

    public function testTheSameZoneShiftsWithDaylightSaving(): void
    {
        // UTC-6 in January, so noon UTC is 6am, not 7. A rule that stored a
        // fixed offset instead of a zone would answer the same in both months.
        $this->assertSame(6, LocalHour::inZone('America/Chicago', 'UTC', $this->at(self::WINTER)));
    }

    public function testAZoneAheadOfUtcReadsLater(): void
    {
        $this->assertSame(21, LocalHour::inZone('Asia/Tokyo', 'UTC', $this->at(self::SUMMER)));
    }

    public function testAZoneCanCrossIntoTheNextDay(): void
    {
        // 23:00 UTC is 08:00 the following morning in Tokyo. The hour is what
        // matters, and it must come from the reader's calendar day, not ours.
        $this->assertSame(8, LocalHour::inZone('Asia/Tokyo', 'UTC', $this->at('2026-07-15 23:00:00')));
    }

    public function testAnEmptyZoneUsesTheSiteZone(): void
    {
        // Most rows have no timezone: the column was never populated, because
        // nothing read it. Those readers get the site's hour, which is what an
        // administrator means by "6am".
        $this->assertSame(7, LocalHour::inZone('', 'America/Chicago', $this->at(self::SUMMER)));
    }

    public function testWhitespaceCountsAsEmpty(): void
    {
        $this->assertSame(7, LocalHour::inZone('   ', 'America/Chicago', $this->at(self::SUMMER)));
    }

    public function testAnUnknownZoneFallsBackRatherThanFailing(): void
    {
        // A stored zone can outlive a tzdata rename. Losing the email would be
        // a worse answer than sending it at the site's hour.
        $this->assertSame(7, LocalHour::inZone('Mars/Olympus_Mons', 'America/Chicago', $this->at(self::SUMMER)));
    }

    public function testBothUnusableFallsBackToUtc(): void
    {
        $this->assertSame(12, LocalHour::inZone('Nowhere/Here', 'Also/Nowhere', $this->at(self::SUMMER)));
    }

    public function testAnOffsetStyleZoneIsAccepted(): void
    {
        // Joomla's own timezone list is region-based, but a hand-edited row or
        // an older site can hold an offset. It is a valid DateTimeZone.
        $this->assertSame(14, LocalHour::inZone('+02:00', 'UTC', $this->at(self::SUMMER)));
    }

    public function testTheAnswerIsAlwaysAnHourOfTheDay(): void
    {
        foreach (\DateTimeZone::listIdentifiers() as $zone) {
            $hour = LocalHour::inZone($zone, 'UTC', $this->at(self::SUMMER));

            $this->assertGreaterThanOrEqual(0, $hour, $zone);
            $this->assertLessThanOrEqual(23, $hour, $zone);
        }
    }
}
