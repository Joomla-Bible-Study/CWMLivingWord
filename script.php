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

            Factory::getApplication()->enqueueMessage(
                sprintf('CWM LivingWord has been updated to version %s.', $version),
                'message'
            );
        }

        return true;
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
}
