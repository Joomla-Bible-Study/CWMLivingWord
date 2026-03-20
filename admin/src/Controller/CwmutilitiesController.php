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

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
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
        '#__livingword',
        '#__livingword_links',
        '#__livingword_plans',
        '#__livingword_plans_details',
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
        $db  = $app->getContainer()->get(DatabaseInterface::class);

        $output   = "-- LivingWord Database Backup\n";
        $output  .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";

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
        $db  = $app->getContainer()->get(DatabaseInterface::class);

        $tableList = [];

        foreach (self::TABLES as $table) {
            $tableList[] = $db->quoteName(str_replace('#__', $app->get('dbprefix'), $table));
        }

        $db->setQuery($command . ' ' . implode(', ', $tableList));
        $db->execute();

        $app->enqueueMessage(Text::_($msgKey));
        $this->setRedirect(Route::_('index.php?option=com_livingword&view=cwmutilities', false));
    }
}
