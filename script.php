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
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
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
        if ($route === 'install') {
            // Seed a menu type + items so users just need to create an
            // mod_menu module and point it at "livingword-menu" to expose
            // the component to the front end.
            $this->seedMenu();

            Factory::getApplication()->enqueueMessage(
                'CWM LivingWord 5.0.0 has been installed successfully.',
                'message'
            );
        }

        if ($route === 'update') {
            // Idempotent — only fires if the menu type does not exist yet,
            // so older installs that add it by hand get it retroactively.
            $this->seedMenu();

            Factory::getApplication()->enqueueMessage(
                'CWM LivingWord has been updated to version 5.0.0.',
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
}
