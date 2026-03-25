<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

/**
 * Database utilities controller (optimize, check, repair, backup)
 *
 * @since  5.0.0
 */
class CwmutilitiesController extends BaseController
{
    /**
     * LivingWord table names.
     *
     * @var string[]
     * @since 5.0.0
     */
    private const TABLES = [
        '#__livingword_users',
        '#__livingword_links',
        '#__livingword_plans',
        '#__livingword_plans_details',
        '#__livingword_progress',
        '#__livingword_groups',
        '#__livingword_group_members',
    ];

    /**
     * Optimize all LivingWord database tables.
     *
     * @return  void
     *
     * @since   5.0.0
     */
    public function optimize(): void
    {
        $this->runTableCommand('OPTIMIZE TABLE', 'COM_LIVINGWORD_TABLES_OPTIMIZED');
    }

    /**
     * Check all LivingWord database tables.
     *
     * @return  void
     *
     * @since   5.0.0
     */
    public function check(): void
    {
        $this->runTableCommand('CHECK TABLE', 'COM_LIVINGWORD_TABLES_CHECKED');
    }

    /**
     * Repair all LivingWord database tables.
     *
     * @return  void
     *
     * @since   5.0.0
     */
    public function repair(): void
    {
        $this->runTableCommand('REPAIR TABLE', 'COM_LIVINGWORD_TABLES_REPAIRED');
    }

    /**
     * Backup all LivingWord tables to SQL file download.
     *
     * @return  void
     *
     * @throws \Exception
     * @since   5.0.0
     */
    public function backup(): void
    {
        $app = $this->app;
        $db  = Factory::getContainer()->get(DatabaseInterface::class);

        $output   = "-- LivingWord Database Backup\n";
        $output .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";

        foreach (self::TABLES as $table) {
            $realTable = str_replace('#__', $app->get('dbprefix'), $table);

            // Get CREATE TABLE statement
            $db->setQuery('SHOW CREATE TABLE ' . $db->quoteName($realTable));
            $createRow = $db->loadRow();

            if ($createRow) {
                $output .= "DROP TABLE IF EXISTS " . $db->quoteName($realTable) . ";\n";
                $output .= $createRow[1] . ";\n\n";
            }

            // Get all rows
            $db->setQuery('SELECT * FROM ' . $db->quoteName($realTable));
            $rows = $db->loadAssocList();

            foreach ($rows as $row) {
                $values = array_map(function ($v) use ($db) {
                    return $v === null ? 'NULL' : $db->quote($v);
                }, $row);

                $output .= 'INSERT INTO ' . $db->quoteName($realTable) . ' VALUES (' . implode(', ', $values) . ");\n";
            }

            $output .= "\n";
        }

        $filename = 'livingword_backup_' . date('Y-m-d_His') . '.sql';

        $app->setHeader('Content-Type', 'application/sql');
        $app->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $app->setHeader('Content-Length', \strlen($output));
        $app->sendHeaders();

        echo $output;

        $app->close();
    }

    /**
     * Import reading plan details from CSV upload.
     *
     * CSV format: day_number, reading, audio_url, description
     * Supports both replace and append modes.
     *
     * @return  void
     *
     * @since   5.8.0
     */
    public function csvimport(): void
    {
        Session::checkToken() or die(Text::_('JINVALID_TOKEN'));

        $app    = $this->app;
        $db     = Factory::getContainer()->get(DatabaseInterface::class);
        $planId = $this->input->getInt('plan_id', 0);
        $mode   = $this->input->getCmd('import_mode', 'append');

        if ($planId <= 0) {
            $app->enqueueMessage(Text::_('COM_LIVINGWORD_CSV_NO_PLAN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_livingword&view=cwmutilities', false));

            return;
        }

        $file = $this->input->files->get('csv_file');

        if (empty($file) || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            $app->enqueueMessage(Text::_('COM_LIVINGWORD_CSV_NO_FILE'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_livingword&view=cwmutilities', false));

            return;
        }

        $handle = fopen($file['tmp_name'], 'r');

        if (!$handle) {
            $app->enqueueMessage(Text::_('COM_LIVINGWORD_CSV_READ_ERROR'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_livingword&view=cwmutilities', false));

            return;
        }

        // If replace mode, delete existing readings
        if ($mode === 'replace') {
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__livingword_plans_details'))
                ->where($db->quoteName('plan_id') . ' = ' . $planId);
            $db->setQuery($query);
            $db->execute();

            $ordering = 1;
        } else {
            // Append: start after the last existing ordering
            $query = $db->getQuery(true)
                ->select('MAX(' . $db->quoteName('ordering') . ')')
                ->from($db->quoteName('#__livingword_plans_details'))
                ->where($db->quoteName('plan_id') . ' = ' . $planId);
            $db->setQuery($query);
            $ordering = (int) $db->loadResult() + 1;
        }

        $imported = 0;
        $errors   = 0;
        $lineNum  = 0;

        try {
            $db->transactionStart();

            while (($row = fgetcsv($handle)) !== false) {
                $lineNum++;

                // Skip header row if present
                if ($lineNum === 1 && isset($row[0]) && strtolower(trim($row[0])) === 'day_number') {
                    continue;
                }

                // Minimum: reading reference in column index 1 (or 0 if no day_number)
                $reading = trim($row[1] ?? $row[0] ?? '');

                if (empty($reading)) {
                    $errors++;

                    continue;
                }

                $audio   = trim($row[2] ?? '');
                $descrip = trim($row[3] ?? '');

                $record = (object) [
                    'plan_id'  => $planId,
                    'ordering' => $ordering,
                    'reading'  => $reading,
                    'audio'    => $audio,
                    'descrip'  => $descrip,
                ];

                $db->insertObject('#__livingword_plans_details', $record);
                $imported++;
                $ordering++;
            }

            $db->transactionCommit();
        } catch (\RuntimeException $e) {
            $db->transactionRollback();
            $errors++;
        }

        fclose($handle);

        $app->enqueueMessage(Text::sprintf('COM_LIVINGWORD_CSV_RESULT', $imported, $errors));
        $this->setRedirect(Route::_('index.php?option=com_livingword&view=cwmutilities', false));
    }

    /**
     * Execute a table maintenance command and redirect.
     *
     * @param   string  $command  SQL command (OPTIMIZE TABLE, CHECK TABLE, etc.)
     * @param   string  $msgKey   Language key for success message
     *
     * @return  void
     *
     * @since   5.0.0
     */
    private function runTableCommand(string $command, string $msgKey): void
    {
        $app = $this->app;
        $db  = Factory::getContainer()->get(DatabaseInterface::class);

        $tableList = [];

        foreach (self::TABLES as $table) {
            $tableList[] = $db->quoteName(str_replace('#__', $app->get('dbprefix'), $table));
        }

        // Maintenance commands cannot use prepared statements
        $db->getConnection()->query($command . ' ' . implode(', ', $tableList));

        $app->enqueueMessage(Text::_($msgKey));
        $this->setRedirect(Route::_('index.php?option=com_livingword&view=cwmutilities', false));
    }
}
