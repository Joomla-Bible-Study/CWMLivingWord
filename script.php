<?php

/**
 * @package    Livingword
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Library\Scripture\Installer\ConsumerRegistry;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Component\Categories\Administrator\Table\CategoryTable;
use Joomla\Database\DatabaseInterface;

/**
 * Script file of com_livingword component
 *
 * @since  5.0.0
 */
class Com_livingwordInstallerScript
{
    /**
     * Minimum PHP version required.
     *
     * @var string
     * @since 5.0.0
     */
    protected string $minimumPhp = '8.3.0';

    /**
     * Minimum Joomla version required.
     *
     * @var string
     * @since 5.0.0
     */
    protected string $minimumJoomla = '5.0.0';

    /**
     * Method to install the extension.
     *
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  bool
     *
     * @since   5.0.0
     */
    public function install(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Method to uninstall the extension.
     *
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  bool
     *
     * @since   5.0.0
     */
    public function uninstall(InstallerAdapter $adapter): bool
    {
        $this->unregisterScriptureConsumer();

        return true;
    }

    /**
     * Method to update the extension.
     *
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  bool
     *
     * @since   5.0.0
     */
    public function update(InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Function called before extension installation/update/removal.
     *
     * @param   string            $route    Which action is happening (install|uninstall|update)
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  bool  True on success
     *
     * @since   5.0.0
     */
    public function preflight(string $route, InstallerAdapter $adapter): bool
    {
        if (version_compare(PHP_VERSION, $this->minimumPhp, '<')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_LIVINGWORD requires PHP %s or later. You are running PHP %s.', $this->minimumPhp, PHP_VERSION),
                'error'
            );

            return false;
        }

        if (version_compare(JVERSION, $this->minimumJoomla, '<')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('COM_LIVINGWORD requires Joomla %s or later.', $this->minimumJoomla),
                'error'
            );

            return false;
        }

        return true;
    }

    /**
     * Function called after extension installation/update/removal.
     *
     * @param   string            $route    Which action is happening (install|uninstall|update)
     * @param   InstallerAdapter  $adapter  The adapter calling this method
     *
     * @return  bool
     *
     * @since   5.0.0
     */
    public function postflight(string $route, InstallerAdapter $adapter): bool
    {
        $version = (string) $adapter->getManifest()->version;

        if ($route === 'install') {
            // Seed a menu type + items so users just need to create an
            // mod_menu module and point it at "livingword-menu" to expose
            // the component to the front end.
            $this->seedMenu();

            // Register #__content_types rows so com_tags can tag plans,
            // links, and groups.
            $this->registerContentTypes();

            $this->ensureActionTokenIndex();
            $this->ensureUserPreferenceColumns();
            $this->ensureProgressPassageKey();

            // Tell lib_cwmscripture this component depends on it, so removing
            // the library is refused while Living Word is installed.
            $this->registerScriptureConsumer();

            Factory::getApplication()->enqueueMessage(
                sprintf('CWM LivingWord %s has been installed successfully.', $version),
                'message'
            );
        }

        if ($route === 'update') {
            // Idempotent — only fires if the menu type does not exist yet,
            // so older installs that add it by hand get it retroactively.
            $this->seedMenu();

            // Migrate legacy free-text link categories into #__categories.
            // Safe no-op once the legacy column is gone (5.6.0+).
            $this->migrateLinkCategories();

            // Re-seed content types on every update so newly added entities
            // (or field-mapping tweaks) reach existing installs.
            $this->registerContentTypes();

            $this->ensureActionTokenIndex();
            $this->ensureUserPreferenceColumns();
            $this->ensureProgressPassageKey();

            // Idempotent, and the repair path for a fresh package install where
            // the library child had not been stored yet at our postflight.
            $this->registerScriptureConsumer();

            Factory::getApplication()->enqueueMessage(
                sprintf('CWM LivingWord has been updated to version %s.', $version),
                'message'
            );
        }

        return true;
    }

    /**
     * Register com_livingword as a consumer of lib_cwmscripture.
     *
     * The library gates two destructive operations on "who still depends on
     * me": its own uninstall, and dropping the shared `#__bsms_*` tables, which
     * hold every locally downloaded translation. Joomla tracks no library
     * dependencies, so the only extensions the library can see are the ones in
     * its `FIRST_PARTY` list or in this registry. com_livingword is currently in
     * that hardcoded list; registering means we no longer depend on staying
     * there. Requires lib_cwmscripture 1.1.6 (#85).
     *
     * The library is a soft dependency — every call site guards on
     * `class_exists()` / `isInstalled()` and falls back to BibleGateway — so a
     * missing registry must never fail the install.
     *
     * @return  void
     *
     * @since   5.7.0
     */
    private function registerScriptureConsumer(): void
    {
        if (!class_exists(ConsumerRegistry::class)) {
            return;
        }

        ConsumerRegistry::register('com_livingword', 'component', name: 'Living Word (com_livingword)');
    }

    /**
     * Drop this component's row from the library's consumer registry.
     *
     * Leaving it behind would not pin the library forever — `installed()` prunes
     * rows whose extension is gone from `#__extensions` — but unregistering is
     * the cheap, explicit half of the contract.
     *
     * @return  void
     *
     * @since   5.7.0
     */
    private function unregisterScriptureConsumer(): void
    {
        if (!class_exists(ConsumerRegistry::class)) {
            return;
        }

        ConsumerRegistry::unregister('com_livingword', 'component');
    }

    /**
     * Ensure #__livingword_users carries the three preference columns.
     *
     * audio_version, email_hour and timezone are in install.mysql.utf8.sql and
     * in no update file, so a site created before they were added never got
     * them — and nothing said so. Joomla's DatabaseDriver::updateObject() reads
     * the table's real columns and silently skips any property that is not one,
     * so saving preferences answered "Your preferences have been saved" and
     * dropped these three every time. Observed on two dev installs; a package
     * install has them, which is why it never showed up in testing.
     *
     * email_hour is the one that bites hardest: the task plugin only mails
     * subscribers whose hour matches the current server hour, so on an affected
     * site the daily email goes out at whatever hour the row happens to hold
     * and the reader cannot change it.
     *
     * In PHP rather than an update file for two reasons. Joomla runs update SQL
     * only when it is newer than the recorded schema version, so a site already
     * past this version would never see it — the same reason
     * ensureActionTokenIndex() exists. And MySQL has no ADD COLUMN IF NOT
     * EXISTS, so a plain ALTER would fail on every site that is already fine.
     *
     * @return  void
     *
     * @since   5.7.0-beta4
     */
    private function ensureUserPreferenceColumns(): void
    {
        $columns = [
            'audio_version' => "varchar(20) NOT NULL DEFAULT '' COMMENT 'Preferred audio bible version'",
            'email_hour'    => "tinyint NOT NULL DEFAULT 6 COMMENT 'Preferred email hour (0-23)'",
            'timezone'      => "varchar(50) NOT NULL DEFAULT '' COMMENT 'User timezone (e.g. America/Chicago)'",
        ];

        try {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $table = $db->replacePrefix('#__livingword_users');

            foreach ($columns as $column => $definition) {
                $query = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->quoteName('information_schema.columns'))
                    ->where($db->quoteName('table_schema') . ' = DATABASE()')
                    ->where($db->quoteName('table_name') . ' = ' . $db->quote($table))
                    ->where($db->quoteName('column_name') . ' = ' . $db->quote($column));

                if ((int) $db->setQuery($query)->loadResult() > 0) {
                    continue;
                }

                // The definition is a literal from the array above, never user
                // input, and column definitions cannot be bound as parameters.
                $db->setQuery(
                    'ALTER TABLE ' . $db->quoteName($table)
                    . ' ADD COLUMN ' . $db->quoteName($column) . ' ' . $definition
                )->execute();

                Factory::getApplication()->enqueueMessage(
                    sprintf('CWM LivingWord: added the missing %s column to #__livingword_users.', $column),
                    'message'
                );
            }
        } catch (\Throwable $e) {
            // Say so rather than fail the install: a missing column costs three
            // preferences, and aborting the update would cost everything.
            Factory::getApplication()->enqueueMessage(
                'CWM LivingWord: could not repair the preference columns — ' . $e->getMessage(),
                'warning'
            );
        }
    }

    /**
     * Ensure #__livingword_progress can hold one row per passage, not per day.
     *
     * Per-passage completion (#7) added `passage_index` and widened the unique
     * key to cover it — in install.mysql.utf8.sql only. No update file was ever
     * written, and CREATE TABLE IF NOT EXISTS does not alter an existing table,
     * so every site that installed before #7 and upgraded still carries the
     * original UNIQUE (user_id, plan_id, day). That key admits exactly one
     * passage per day: the reader ticks passage 2, the request returns 200, and
     * the row is rejected. Measured on a carried-forward dev install, 42 of the
     * rows a full seed writes were refused; a fresh install refused none.
     *
     * Nothing complained because CwmprogressHelper::markPassageComplete()
     * swallowed the violation as "already marked" — narrowed in this release so
     * a genuinely lost write is no longer silent.
     *
     * In PHP rather than an update file for the reason ensureActionTokenIndex()
     * gives: Joomla runs update SQL only when it is newer than the recorded
     * schema version, and MySQL has no DROP/ADD INDEX IF EXISTS, so a plain
     * ALTER would fail on every site that is already correct.
     *
     * Matched on shape, not on name — a hand-patched site may carry the narrow
     * key under a different one — and ordered so the wide key is in place before
     * the narrow key goes: if the ALTER fails, the table keeps the protection it
     * had. Widening a unique key can never fail on existing data.
     *
     * @return  void
     *
     * @since   5.7.0
     */
    private function ensureProgressPassageKey(): void
    {
        $narrowColumns = ['user_id', 'plan_id', 'day'];
        $wideColumns   = [...$narrowColumns, 'passage_index'];

        try {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $table = $db->replacePrefix('#__livingword_progress');

            $columnExists = $db->setQuery(
                $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->quoteName('information_schema.columns'))
                    ->where($db->quoteName('table_schema') . ' = DATABASE()')
                    ->where($db->quoteName('table_name') . ' = ' . $db->quote($table))
                    ->where($db->quoteName('column_name') . ' = ' . $db->quote('passage_index'))
            )->loadResult();

            // An upgraded site can be missing the column as well as the key —
            // insertObject() then fails on "unknown column" instead, which the
            // same catch used to hide.
            if ((int) $columnExists === 0) {
                $db->setQuery(
                    'ALTER TABLE ' . $db->quoteName($table)
                    . ' ADD COLUMN ' . $db->quoteName('passage_index')
                    . " smallint UNSIGNED NOT NULL DEFAULT 0"
                    . " COMMENT '0-based passage index within the day reading'"
                    . ' AFTER ' . $db->quoteName('day')
                )->execute();
            }

            $rows = $db->setQuery(
                $db->getQuery(true)
                    ->select(
                        $db->quoteName(
                            ['index_name', 'non_unique', 'column_name'],
                            ['name', 'nonUnique', 'column']
                        )
                    )
                    ->from($db->quoteName('information_schema.statistics'))
                    ->where($db->quoteName('table_schema') . ' = DATABASE()')
                    ->where($db->quoteName('table_name') . ' = ' . $db->quote($table))
                    ->order($db->quoteName('index_name') . ', ' . $db->quoteName('seq_in_index'))
            )->loadObjectList();

            $indexes = [];

            foreach ($rows as $row) {
                $indexes[$row->name]['unique']    = (int) $row->nonUnique === 0;
                $indexes[$row->name]['columns'][] = $row->column;
            }

            $hasWide = false;

            foreach ($indexes as $spec) {
                if ($spec['unique'] && array_diff($wideColumns, $spec['columns']) === []) {
                    $hasWide = true;
                }
            }

            $repaired = false;

            if (!$hasWide && !isset($indexes['idx_user_plan_day_passage'])) {
                $db->setQuery(
                    'ALTER TABLE ' . $db->quoteName($table)
                    . ' ADD UNIQUE KEY ' . $db->quoteName('idx_user_plan_day_passage')
                    . ' (' . implode(', ', $db->quoteName($wideColumns)) . ')'
                )->execute();

                $hasWide  = true;
                $repaired = true;
            }

            // Only now, with the wide key in place, is it safe to give up the
            // narrow one — any unique key over a subset of (user_id, plan_id,
            // day), whatever it is called.
            if ($hasWide) {
                foreach ($indexes as $name => $spec) {
                    if (
                        $name === 'PRIMARY'
                        || !$spec['unique']
                        || array_diff($spec['columns'], $narrowColumns) !== []
                    ) {
                        continue;
                    }

                    $db->setQuery(
                        'ALTER TABLE ' . $db->quoteName($table)
                        . ' DROP INDEX ' . $db->quoteName($name)
                    )->execute();

                    unset($indexes[$name]);
                    $repaired = true;
                }
            }

            // The shipped schema keeps a plain (user_id, plan_id, day) index for
            // the per-day lookups; dropping the narrow unique above takes it
            // with it on an affected site.
            if ($hasWide && !isset($indexes['idx_user_plan_day'])) {
                $db->setQuery(
                    'ALTER TABLE ' . $db->quoteName($table)
                    . ' ADD KEY ' . $db->quoteName('idx_user_plan_day')
                    . ' (' . implode(', ', $db->quoteName($narrowColumns)) . ')'
                )->execute();
            }

            if ($repaired) {
                // Worth saying out loud: it explains why days completed before
                // the repair can still show as partially read. Those days hold
                // the one row the old key allowed, and which passage it records
                // is whichever the reader ticked first — so the missing rows
                // cannot be backfilled without inventing progress.
                Factory::getApplication()->enqueueMessage(
                    'CWM LivingWord: repaired the reading-progress unique key, which had been limiting'
                    . ' this site to one passage per day. Days completed before now may still show as'
                    . ' partially read — re-ticking their passages records them properly.',
                    'message'
                );
            }
        } catch (\Throwable $e) {
            // Say so rather than fail the update: the site keeps working, and
            // the reader loses per-passage ticks, not the component.
            Factory::getApplication()->enqueueMessage(
                'CWM LivingWord: could not repair the reading-progress unique key — ' . $e->getMessage(),
                'warning'
            );
        }
    }

    /**
     * Ensure #__livingword_users carries the idx_action_token index.
     *
     * install.mysql.utf8.sql created the action_token column without its index
     * while 5.3.0.sql added both, so any site installed fresh from the shipped
     * package has the column and not the index. Update SQL cannot repair them:
     * Joomla only runs files newer than the recorded #__schemas version, so
     * 5.3.0.sql never fires for a site that installed at 5.6.0.
     *
     * CwmuserHelper::findByActionToken() filters on the column directly — the
     * one-click "mark as read" link in every reading email — so without the
     * index each click is a full scan of the users table.
     *
     * Idempotent: checks information_schema first, because MySQL has no
     * ADD KEY IF NOT EXISTS and a duplicate-key error would surface as a
     * failed install.
     *
     * @return  void
     *
     * @since   5.6.0
     */
    private function ensureActionTokenIndex(): void
    {
        try {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $table = $db->replacePrefix('#__livingword_users');

            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('information_schema.statistics'))
                ->where($db->quoteName('table_schema') . ' = DATABASE()')
                ->where($db->quoteName('table_name') . ' = ' . $db->quote($table))
                ->where($db->quoteName('index_name') . ' = ' . $db->quote('idx_action_token'));

            if ((int) $db->setQuery($query)->loadResult() > 0) {
                return;
            }

            $db->setQuery(
                'ALTER TABLE ' . $db->quoteName($table)
                . ' ADD KEY ' . $db->quoteName('idx_action_token')
                . ' (' . $db->quoteName('action_token') . ')'
            )->execute();
        } catch (\Throwable $e) {
            // A missing index costs performance, never correctness. Failing the
            // install over it would be the worse outcome.
            Factory::getApplication()->enqueueMessage(
                'CWM LivingWord: could not create idx_action_token — ' . $e->getMessage(),
                'warning'
            );
        }
    }

    /**
     * Create a pre-wired "livingword-menu" menu type with all site views
     * as menu items, if it doesn't already exist.
     *
     * Users publish a mod_menu pointing at this menutype when they're ready
     * — the items themselves ship invisible by default.
     *
     * @return  void
     *
     * @since   5.0.0
     */
    private function seedMenu(): void
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            // Skip if the menu type already exists.
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__menu_types'))
                ->where($db->quoteName('menutype') . ' = ' . $db->quote('livingword-menu'));
            $db->setQuery($query);

            if ((int) $db->loadResult() > 0) {
                return;
            }

            // Resolve component extension_id so menu items route correctly.
            $query = $db->getQuery(true)
                ->select($db->quoteName('extension_id'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_livingword'));
            $db->setQuery($query);
            $componentId = (int) $db->loadResult();

            if ($componentId === 0) {
                return;
            }

            // Create the menu type via its table class — this handles asset
            // creation cleanly.
            $typeTable = Table::getInstance('MenuType');
            $typeTable->save([
                'menutype'    => 'livingword-menu',
                'title'       => 'LivingWord Menu',
                'description' => 'Pre-wired menu items for LivingWord. Create a mod_menu module and point it at this menu type to expose the component to visitors.',
                'client_id'   => 0,
            ]);

            // Insert menu items directly. The Table\Menu save() loop above
            // was chosen first but its internal nested-set cursor silently
            // drifted across iterations, leaving items 3+ with parent_id=0
            // level=0. Direct INSERTs + a single rebuild() at the end is
            // more predictable.
            $items = [
                ['cwmhome',      'Home',         'livingword-home'],
                ['cwmplanview',  'Reading Plan', 'livingword-plan'],
                ['cwmresources', 'Resources',    'livingword-resources'],
                ['cwmtools',     'Bible Tools',  'livingword-tools'],
                ['cwmgroups',    'Groups',       'livingword-groups'],
                ['cwmsettings',  'My Settings',  'livingword-settings'],
            ];

            foreach ($items as [$view, $title, $alias]) {
                $columns = [
                    'menutype', 'title', 'alias', 'note', 'path', 'link', 'type',
                    'published', 'parent_id', 'level', 'component_id',
                    'checked_out', 'checked_out_time', 'browserNav', 'access',
                    'img', 'template_style_id', 'params', 'lft', 'rgt', 'home',
                    'language', 'client_id', 'publish_up', 'publish_down',
                ];

                $values = [
                    $db->quote('livingword-menu'),
                    $db->quote($title),
                    $db->quote($alias),
                    $db->quote(''),
                    $db->quote($alias),
                    $db->quote('index.php?option=com_livingword&view=' . $view),
                    $db->quote('component'),
                    1,
                    1,
                    1,
                    $componentId,
                    'NULL',
                    'NULL',
                    0,
                    1,
                    $db->quote(''),
                    0,
                    $db->quote('{}'),
                    0,
                    1,
                    0,
                    $db->quote('*'),
                    0,
                    'NULL',
                    'NULL',
                ];

                $insert = $db->getQuery(true)
                    ->insert($db->quoteName('#__menu'))
                    ->columns($db->quoteName($columns))
                    ->values(implode(',', $values));
                $db->setQuery($insert);
                $db->execute();
            }

            // Single nested-set rebuild restores lft/rgt for every item now
            // that the tree's row set is complete.
            $menuTable = Table::getInstance('Menu');
            $menuTable->rebuild();
        } catch (\Throwable $e) {
            Factory::getApplication()->enqueueMessage(
                'LivingWord: could not seed starter menu — ' . $e->getMessage(),
                'warning'
            );
        }
    }

    /**
     * Migrate legacy free-text `category` values on #__livingword_links into
     * proper #__categories rows under the `com_livingword.link` extension
     * namespace, populating the new `catid` FK. Idempotent — safe to re-run.
     *
     * Bails silently once the legacy column has been dropped (5.6.0+).
     *
     * @return  void
     *
     * @since   5.5.0
     */
    private function migrateLinkCategories(): void
    {
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            // Bail if the legacy column is already gone (post-5.6.0 reinstall).
            $columns = $db->setQuery("SHOW COLUMNS FROM `#__livingword_links` LIKE 'category'")->loadObjectList();

            if ($columns === []) {
                return;
            }

            // Collect distinct non-empty text categories, deduped case-insensitively
            // and trimmed. First-seen casing wins as the canonical title.
            $rows = $db->setQuery(
                "SELECT TRIM(`category`) AS raw
                 FROM `#__livingword_links`
                 WHERE TRIM(`category`) <> ''
                 GROUP BY LOWER(TRIM(`category`))
                 ORDER BY MIN(`id`)"
            )->loadColumn();

            if ($rows === []) {
                return;
            }

            $categoryTable = new CategoryTable($db);
            $createdCount  = 0;
            $linkedCount   = 0;

            foreach ($rows as $title) {
                $title = (string) $title;

                if ($title === '') {
                    continue;
                }

                // Reuse if already migrated.
                $existingId = (int) $db->setQuery(
                    $db->getQuery(true)
                        ->select('id')
                        ->from($db->quoteName('#__categories'))
                        ->where($db->quoteName('extension') . ' = ' . $db->quote('com_livingword.link'))
                        ->where('LOWER(' . $db->quoteName('title') . ') = ' . $db->quote(strtolower($title)))
                )->loadResult();

                if ($existingId === 0) {
                    $categoryTable->reset();
                    $categoryTable->id        = 0;
                    $categoryTable->extension = 'com_livingword.link';
                    $categoryTable->title     = $title;
                    $categoryTable->alias     = OutputFilter::stringURLSafe($title);
                    $categoryTable->parent_id = 1;
                    $categoryTable->path      = $categoryTable->alias;
                    $categoryTable->published = 1;
                    $categoryTable->access    = 1;
                    $categoryTable->language  = '*';
                    $categoryTable->params    = '{}';
                    $categoryTable->metadata  = '{}';
                    $categoryTable->setLocation(1, 'last-child');

                    if (!$categoryTable->check() || !$categoryTable->store()) {
                        Factory::getApplication()->enqueueMessage(
                            'LivingWord: failed to create category "' . $title . '" — ' . $categoryTable->getError(),
                            'warning'
                        );
                        continue;
                    }

                    $existingId = (int) $categoryTable->id;
                    $createdCount++;
                }

                $update = $db->getQuery(true)
                    ->update($db->quoteName('#__livingword_links'))
                    ->set($db->quoteName('catid') . ' = ' . (int) $existingId)
                    ->where('LOWER(TRIM(' . $db->quoteName('category') . ')) = ' . $db->quote(strtolower($title)))
                    ->where($db->quoteName('catid') . ' = 0');
                $db->setQuery($update);
                $db->execute();
                $linkedCount += (int) $db->getAffectedRows();
            }

            // Rebuild the nested set in one pass so lft/rgt/level/path stay coherent
            // even if any per-row store() left the tree slightly skewed.
            (new CategoryTable($db))->rebuild();

            if ($createdCount > 0 || $linkedCount > 0) {
                Factory::getApplication()->enqueueMessage(
                    sprintf(
                        'LivingWord: migrated %d link(s) into %d category(ies). Review them at System → Categories → com_livingword.link. The legacy "category" text column is preserved and will be dropped in 5.6.0.',
                        $linkedCount,
                        $createdCount
                    ),
                    'message'
                );
            }
        } catch (\Throwable $e) {
            Factory::getApplication()->enqueueMessage(
                'LivingWord: link category migration failed — ' . $e->getMessage(),
                'warning'
            );
        }
    }

    /**
     * Seed (or refresh) #__content_types rows for our taggable entities so
     * com_tags can list/route them. Idempotent — UPDATEs on re-run so we
     * never duplicate, and so field-mapping changes ship automatically.
     *
     * @return  void
     *
     * @since   5.6.0
     */
    private function registerContentTypes(): void
    {
        $namespace = 'CWM\\\\Component\\\\Livingword\\\\Administrator\\\\Table\\\\';
        $corePrefix = 'Joomla\\\\CMS\\\\Table\\\\';

        $entities = [
            [
                'alias'      => 'com_livingword.plan',
                'title'      => 'Plan',
                'tableClass' => 'CwmplanTable',
                'dbTable'    => '#__livingword_plans',
                'mappings'   => [
                    'core_content_item_id' => 'id',
                    'core_title'           => 'title',
                    'core_state'           => 'published',
                    'core_alias'           => 'alias',
                    'core_created_time'    => 'created',
                    'core_modified_time'   => 'modified',
                    'core_body'            => 'description',
                    'core_hits'            => 'null',
                    'core_publish_up'      => 'null',
                    'core_publish_down'    => 'null',
                    'core_access'          => 'null',
                    'core_params'          => 'null',
                    'core_featured'        => 'null',
                    'core_metadata'        => 'null',
                    'core_language'        => 'null',
                    'core_images'          => 'null',
                    'core_urls'            => 'null',
                    'core_version'         => 'null',
                    'core_ordering'        => 'ordering',
                    'core_metakey'         => 'null',
                    'core_metadesc'        => 'null',
                    'core_catid'           => 'null',
                    'asset_id'             => 'null',
                ],
                'formFile'   => 'administrator/components/com_livingword/forms/plan.xml',
            ],
            [
                'alias'      => 'com_livingword.link',
                'title'      => 'Link',
                'tableClass' => 'CwmlinkTable',
                'dbTable'    => '#__livingword_links',
                'mappings'   => [
                    'core_content_item_id' => 'id',
                    'core_title'           => 'name',
                    'core_state'           => 'published',
                    'core_alias'           => 'null',
                    'core_created_time'    => 'null',
                    'core_modified_time'   => 'null',
                    'core_body'            => 'null',
                    'core_hits'            => 'null',
                    'core_publish_up'      => 'null',
                    'core_publish_down'    => 'null',
                    'core_access'          => 'null',
                    'core_params'          => 'null',
                    'core_featured'        => 'null',
                    'core_metadata'        => 'null',
                    'core_language'        => 'null',
                    'core_images'          => 'null',
                    'core_urls'            => 'url',
                    'core_version'         => 'null',
                    'core_ordering'        => 'ordering',
                    'core_metakey'         => 'null',
                    'core_metadesc'        => 'null',
                    'core_catid'           => 'catid',
                    'asset_id'             => 'null',
                ],
                'formFile'   => 'administrator/components/com_livingword/forms/link.xml',
            ],
            [
                'alias'      => 'com_livingword.group',
                'title'      => 'Group',
                'tableClass' => 'CwmgroupTable',
                'dbTable'    => '#__livingword_groups',
                'mappings'   => [
                    'core_content_item_id' => 'id',
                    'core_title'           => 'name',
                    'core_state'           => 'published',
                    'core_alias'           => 'null',
                    'core_created_time'    => 'created',
                    'core_modified_time'   => 'modified',
                    'core_body'            => 'description',
                    'core_hits'            => 'null',
                    'core_publish_up'      => 'null',
                    'core_publish_down'    => 'null',
                    'core_access'          => 'null',
                    'core_params'          => 'null',
                    'core_featured'        => 'null',
                    'core_metadata'        => 'null',
                    'core_language'        => 'null',
                    'core_images'          => 'null',
                    'core_urls'            => 'null',
                    'core_version'         => 'null',
                    'core_ordering'        => 'ordering',
                    'core_metakey'         => 'null',
                    'core_metadesc'        => 'null',
                    'core_catid'           => 'null',
                    'asset_id'             => 'null',
                ],
                'formFile'   => 'administrator/components/com_livingword/forms/group.xml',
            ],
        ];

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            foreach ($entities as $entity) {
                $tableJson = json_encode([
                    'special' => [
                        'dbtable' => $entity['dbTable'],
                        'key'     => 'id',
                        'type'    => $entity['tableClass'],
                        'prefix'  => $namespace,
                        'config'  => 'array()',
                    ],
                    'common' => [
                        'dbtable' => '#__ucm_content',
                        'key'     => 'ucm_id',
                        'type'    => 'Corecontent',
                        'prefix'  => $corePrefix,
                        'config'  => 'array()',
                    ],
                ]);

                $fieldMappings = json_encode([
                    'common'  => $entity['mappings'],
                    'special' => new \stdClass(),
                ]);

                $historyOptions = json_encode([
                    'formFile'      => $entity['formFile'],
                    'hideFields'    => ['checked_out', 'checked_out_time'],
                    'ignoreChanges' => ['modified', 'checked_out', 'checked_out_time'],
                    'convertToInt'  => ['ordering', 'published'],
                    'displayLookup' => [
                        [
                            'sourceColumn' => 'tags',
                            'targetTable'  => '#__tags',
                            'targetColumn' => 'id',
                            'displayColumn' => 'title',
                        ],
                    ],
                ]);

                $existingId = (int) $db->setQuery(
                    $db->getQuery(true)
                        ->select($db->quoteName('type_id'))
                        ->from($db->quoteName('#__content_types'))
                        ->where($db->quoteName('type_alias') . ' = ' . $db->quote($entity['alias']))
                )->loadResult();

                if ($existingId > 0) {
                    $update = $db->getQuery(true)
                        ->update($db->quoteName('#__content_types'))
                        ->set($db->quoteName('type_title') . ' = ' . $db->quote($entity['title']))
                        ->set($db->quoteName('table') . ' = ' . $db->quote($tableJson))
                        ->set($db->quoteName('rules') . ' = ' . $db->quote(''))
                        ->set($db->quoteName('field_mappings') . ' = ' . $db->quote($fieldMappings))
                        ->set($db->quoteName('router') . ' = ' . $db->quote(''))
                        ->set($db->quoteName('content_history_options') . ' = ' . $db->quote($historyOptions))
                        ->where($db->quoteName('type_id') . ' = ' . $existingId);
                    $db->setQuery($update)->execute();

                    continue;
                }

                $insert = $db->getQuery(true)
                    ->insert($db->quoteName('#__content_types'))
                    ->columns($db->quoteName([
                        'type_title',
                        'type_alias',
                        'table',
                        'rules',
                        'field_mappings',
                        'router',
                        'content_history_options',
                    ]))
                    ->values(implode(',', [
                        $db->quote($entity['title']),
                        $db->quote($entity['alias']),
                        $db->quote($tableJson),
                        $db->quote(''),
                        $db->quote($fieldMappings),
                        $db->quote(''),
                        $db->quote($historyOptions),
                    ]));
                $db->setQuery($insert)->execute();
            }
        } catch (\Throwable $e) {
            Factory::getApplication()->enqueueMessage(
                'LivingWord: could not register content types — ' . $e->getMessage(),
                'warning'
            );
        }
    }
}
