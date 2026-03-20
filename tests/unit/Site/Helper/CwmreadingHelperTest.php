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
    /**
     * Test that getCurrentReadingDay returns day 1 on the start date.
     *
     * @return void
     *
     * @since  5.0.0
     */
    public function testGetCurrentReadingDayOnStartDate(): void
    {
        $today = date('Y-m-d');

        $result = CwmreadingHelper::getCurrentReadingDay($today, 0, 365);

        $this->assertSame(1, $result);
    }

    /**
     * Test that getCurrentReadingDay wraps around after total days.
     *
     * @return void
     *
     * @since  5.0.0
     */
    public function testGetCurrentReadingDayWrapsAround(): void
    {
        $startDate = date('Y-m-d', strtotime('-365 days'));

        $result = CwmreadingHelper::getCurrentReadingDay($startDate, 0, 365);

        $this->assertSame(1, $result);
    }

    /**
     * Test that offset shifts the reading day.
     *
     * @return void
     *
     * @since  5.0.0
     */
    public function testGetCurrentReadingDayWithOffset(): void
    {
        $today = date('Y-m-d');

        $result = CwmreadingHelper::getCurrentReadingDay($today, 5, 365);

        $this->assertSame(6, $result);
    }

    /**
     * Test dateDiff returns correct number of days.
     *
     * @return void
     *
     * @since  5.0.0
     */
    public function testDateDiff(): void
    {
        $result = CwmreadingHelper::dateDiff('2026-03-20', '2026-03-10');

        $this->assertSame(10, $result);
    }

    /**
     * Test dateDiff with same date returns zero.
     *
     * @return void
     *
     * @since  5.0.0
     */
    public function testDateDiffSameDate(): void
    {
        $result = CwmreadingHelper::dateDiff('2026-01-01', '2026-01-01');

        $this->assertSame(0, $result);
    }

    /**
     * Test empty start date defaults to Jan 1 of current year.
     *
     * @return void
     *
     * @since  5.0.0
     */
    public function testGetCurrentReadingDayEmptyStartDate(): void
    {
        $result = CwmreadingHelper::getCurrentReadingDay('', 0, 365);

        // Should not throw, should return a valid day
        $this->assertGreaterThanOrEqual(1, $result);
        $this->assertLessThanOrEqual(365, $result);
    }
}
