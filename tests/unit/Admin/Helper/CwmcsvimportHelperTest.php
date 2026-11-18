<?php

/**
 * @package    Livingword.Tests
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Tests\Admin\Helper;

use CWM\Component\Livingword\Administrator\Helper\CwmcsvimportHelper;
use PHPUnit\Framework\TestCase;

/**
 * How the reading-plan CSV import reads its file.
 *
 * Written after PHPCompatibility found the import calling fgetcsv() without
 * $escape, which PHP 8.4 deprecates, so every import raised a deprecation on
 * 8.4 and 8.5 sites.
 *
 * @since  __DEPLOY_VERSION__
 */
class CwmcsvimportHelperTest extends TestCase
{
    /**
     * This collects the deprecations raised while a file is read. PHP 8.3
     * raises none either way, so the test only bites on 8.4 and 8.5. Passing
     * '\\', the old default, must not change how a single row parses. The last
     * row is the one that would show it: with '' for $escape, the value PHP
     * recommends, its backslash-escaped quotes split the description in two.
     */
    public function testRowsRaiseNoDeprecationAndParseAsBefore(): void
    {
        // The backslashes are literal: single-quoted strings keep them.
        $handle = fopen('php://memory', 'r+');
        fwrite(
            $handle,
            implode("\n", [
                'day_number,reading,audio_url,description',
                '1,Genesis 1-3,https://example.org/audio/gen1.mp3,In the beginning',
                '',
                '2,"Romans 5-8,12",,"Read \"slowly\", then pray"',
            ]) . "\n"
        );
        rewind($handle);

        $deprecations = [];

        set_error_handler(
            static function (int $errno, string $message) use (&$deprecations): bool {
                $deprecations[] = $message;

                return true;
            },
            E_DEPRECATED | E_USER_DEPRECATED
        );

        try {
            // A generator reads nothing until it is iterated, so the rows are
            // collected here, while the handler is listening.
            $rows = iterator_to_array(CwmcsvimportHelper::rows($handle), false);
        } finally {
            restore_error_handler();
            fclose($handle);
        }

        $this->assertSame([], $deprecations);
        $this->assertSame(
            [
                ['day_number', 'reading', 'audio_url', 'description'],
                ['1', 'Genesis 1-3', 'https://example.org/audio/gen1.mp3', 'In the beginning'],
                [null],
                ['2', 'Romans 5-8,12', '', 'Read \"slowly\", then pray'],
            ],
            $rows
        );
    }
}
