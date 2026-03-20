<?php

/**
 * @package    Livingword.Tests
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Tests\Site\Helper;

use CWM\Component\Livingword\Site\Helper\CwmbiblegatewayHelper;
use PHPUnit\Framework\TestCase;

/**
 * Tests for BibleGateway helper URL construction and reference parsing.
 *
 * @since  5.0.0
 */
class CwmbiblegatewayHelperTest extends TestCase
{
    /**
     * Test parseReadingReference converts LWBIBLEBOOK format to readable text.
     *
     * @return void
     *
     * @since  5.0.0
     */
    public function testParseReadingReferenceWithBookMap(): void
    {
        $bookMap = [
            25 => 'Lamentations',
            50 => 'Philippians',
        ];

        $result = CwmbiblegatewayHelper::parseReadingReference(
            'LWBIBLEBOOK25 1-3;LWBIBLEBOOK50 12',
            $bookMap
        );

        $this->assertSame('Lamentations 1-3; Philippians 12', $result);
    }

    /**
     * Test parseReadingReference with unknown book number falls back.
     *
     * @return void
     *
     * @since  5.0.0
     */
    public function testParseReadingReferenceUnknownBook(): void
    {
        $result = CwmbiblegatewayHelper::parseReadingReference('LWBIBLEBOOK99 1-3');

        $this->assertSame('Book 99 1-3', $result);
    }

    /**
     * Test parseReadingReference with empty string returns empty.
     *
     * @return void
     *
     * @since  5.0.0
     */
    public function testParseReadingReferenceEmpty(): void
    {
        $result = CwmbiblegatewayHelper::parseReadingReference('');

        $this->assertSame('', $result);
    }

    /**
     * Test buildReadingLink produces valid HTML.
     *
     * @return void
     *
     * @since  5.0.0
     */
    public function testBuildReadingLinkHtml(): void
    {
        $result = CwmbiblegatewayHelper::buildReadingLink('Genesis 1-3', 'NLT');

        $this->assertStringContainsString('href="', $result);
        $this->assertStringContainsString('target="_blank"', $result);
        $this->assertStringContainsString('Genesis 1-3', $result);
        $this->assertStringContainsString('NLT', $result);
    }

    /**
     * Test buildReadingLink escapes special characters.
     *
     * @return void
     *
     * @since  5.0.0
     */
    public function testBuildReadingLinkEscapesHtml(): void
    {
        $result = CwmbiblegatewayHelper::buildReadingLink('Song of Solomon 1 <test>', 'KJV');

        $this->assertStringContainsString('&lt;test&gt;', $result);
    }
}
