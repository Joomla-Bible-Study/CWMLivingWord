<?php

/**
 * @package    Livingword.Tests
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Tests\Site\Helper;

use CWM\Component\Livingword\Site\Helper\CwmreadingHelper;
use PHPUnit\Framework\TestCase;

/**
 * Tests for reading plan date calculations.
 *
 * @since  5.0.0
 */
class CwmreadingHelperTest extends TestCase
{
    // ── getCurrentReadingDay (annual) ──

    public function testGetCurrentReadingDayOnStartDate(): void
    {
        $today = date('Y-m-d');

        $this->assertSame(1, CwmreadingHelper::getCurrentReadingDay($today, 0, 365));
    }

    public function testGetCurrentReadingDayWrapsAround(): void
    {
        $startDate = date('Y-m-d', strtotime('-365 days'));

        $this->assertSame(1, CwmreadingHelper::getCurrentReadingDay($startDate, 0, 365));
    }

    public function testGetCurrentReadingDayWithOffset(): void
    {
        $today = date('Y-m-d');

        $this->assertSame(6, CwmreadingHelper::getCurrentReadingDay($today, 5, 365));
    }

    public function testGetCurrentReadingDayEmptyStartDate(): void
    {
        $result = CwmreadingHelper::getCurrentReadingDay('', 0, 365);

        $this->assertGreaterThanOrEqual(1, $result);
        $this->assertLessThanOrEqual(365, $result);
    }

    public function testGetCurrentReadingDayZeroDate(): void
    {
        $result = CwmreadingHelper::getCurrentReadingDay('0000-00-00', 0, 365);

        $this->assertGreaterThanOrEqual(1, $result);
        $this->assertLessThanOrEqual(365, $result);
    }

    public function testGetCurrentReadingDayMidPlan(): void
    {
        $startDate = date('Y-m-d', strtotime('-10 days'));

        $this->assertSame(11, CwmreadingHelper::getCurrentReadingDay($startDate, 0, 365));
    }

    public function testGetCurrentReadingDayNegativeOffset(): void
    {
        $startDate = date('Y-m-d', strtotime('-10 days'));

        // Day 11 with offset -5 = day 6
        $this->assertSame(6, CwmreadingHelper::getCurrentReadingDay($startDate, -5, 365));
    }

    public function testGetCurrentReadingDayShortPlan(): void
    {
        // 21-day plan started 25 days ago should wrap to day 5
        $startDate = date('Y-m-d', strtotime('-25 days'));

        $this->assertSame(5, CwmreadingHelper::getCurrentReadingDay($startDate, 0, 21));
    }

    // ── getFixedReadingDay ──

    public function testGetFixedReadingDayOnStartDate(): void
    {
        $today = date('Y-m-d');

        $this->assertSame(1, CwmreadingHelper::getFixedReadingDay($today, 0, 21));
    }

    public function testGetFixedReadingDayMidPlan(): void
    {
        $startDate = date('Y-m-d', strtotime('-10 days'));

        $this->assertSame(11, CwmreadingHelper::getFixedReadingDay($startDate, 0, 21));
    }

    public function testGetFixedReadingDayCapsAtTotal(): void
    {
        // 21-day plan started 30 days ago — should cap at 21, not wrap
        $startDate = date('Y-m-d', strtotime('-30 days'));

        $this->assertSame(21, CwmreadingHelper::getFixedReadingDay($startDate, 0, 21));
    }

    public function testGetFixedReadingDayNotStartedYet(): void
    {
        $futureDate = date('Y-m-d', strtotime('+5 days'));

        $this->assertSame(0, CwmreadingHelper::getFixedReadingDay($futureDate, 0, 21));
    }

    public function testGetFixedReadingDayEmptyStartDate(): void
    {
        $this->assertSame(1, CwmreadingHelper::getFixedReadingDay('', 0, 21));
    }

    public function testGetFixedReadingDayWithOffset(): void
    {
        $startDate = date('Y-m-d', strtotime('-5 days'));

        // Day 6 + offset 3 = day 9
        $this->assertSame(9, CwmreadingHelper::getFixedReadingDay($startDate, 3, 21));
    }

    // ── dateDiff ──

    public function testDateDiff(): void
    {
        $this->assertSame(10, CwmreadingHelper::dateDiff('2026-03-20', '2026-03-10'));
    }

    public function testDateDiffSameDate(): void
    {
        $this->assertSame(0, CwmreadingHelper::dateDiff('2026-01-01', '2026-01-01'));
    }

    public function testDateDiffReversed(): void
    {
        // DateTime::diff always returns absolute value
        $this->assertSame(10, CwmreadingHelper::dateDiff('2026-03-10', '2026-03-20'));
    }

    public function testDateDiffAcrossYear(): void
    {
        $this->assertSame(365, CwmreadingHelper::dateDiff('2027-01-01', '2026-01-01'));
    }
}
