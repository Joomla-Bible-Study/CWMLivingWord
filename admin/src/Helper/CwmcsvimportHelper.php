<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

/**
 * Reads the file behind the reading-plan CSV import.
 *
 * Deliberately free of Joomla, so a unit test can reach the fgetcsv() call. The
 * import itself is CwmutilitiesController::csvimport(), which needs a session
 * token, a database and an uploaded file before it reads a single row.
 *
 * @since  __DEPLOY_VERSION__
 */
final class CwmcsvimportHelper
{
    /**
     * Each row of an open CSV file, in order, as fgetcsv() returns it.
     *
     * Rows are read one at a time as the caller asks for them, so the import
     * still writes each row before it reads the next. A blank line comes back
     * as [null], as it always has, and the import counts it as an error.
     *
     * @param   resource  $handle  Open file handle, positioned at the first row
     *
     * @return  \Generator<int, array<int, string|null>>
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function rows($handle): \Generator
    {
        // PHP 8.4 deprecates leaving out fgetcsv()'s $escape. '\\' is the value
        // it defaulted to, so every file parses exactly as before.
        while (($row = fgetcsv($handle, null, ',', '"', '\\')) !== false) {
            yield $row;
        }
    }
}
