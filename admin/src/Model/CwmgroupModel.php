<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;

/**
 * Group edit model
 *
 * @since  5.7.0
 */
class CwmgroupModel extends AdminModel
{
    /**
     * @param   array  $data      Data for the form.
     * @param   bool   $loadData  True if the form is to load its own data.
     *
     * @return  mixed  A JForm object on success, false on failure
     *
     * @throws \Exception
     * @since 5.7.0
     */
    public function getForm($data = [], $loadData = true): mixed
    {
        $form = $this->loadForm('com_livingword.group', 'group', ['control' => 'jform', 'load_data' => $loadData]);

        if ($form === null) {
            return false;
        }

        if (!$this->canEditState((object) $data)) {
            $form->setFieldAttribute('ordering', 'disabled', 'true');
            $form->setFieldAttribute('published', 'disabled', 'true');
            $form->setFieldAttribute('ordering', 'filter', 'unset');
            $form->setFieldAttribute('published', 'filter', 'unset');
        }

        return $form;
    }

    /**
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array. Optional.
     *
     * @return  Table
     *
     * @throws  \Exception
     * @since   5.7.0
     */
    public function getTable($name = 'Cwmgroup', $prefix = '', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Load a single group and attach its current tag IDs so the edit form's
     * tag field pre-populates. AdminModel::save() handles tag persistence
     * automatically via plg_behaviour_taggable.
     *
     * @param   int|null  $pk  The id of the row to fetch.
     *
     * @return  mixed  Group object on success, false on failure.
     *
     * @since   5.6.0
     */
    #[\Override]
    public function getItem($pk = null): mixed
    {
        $item = parent::getItem($pk);

        if ($item && !empty($item->id)) {
            $item->tags = new TagsHelper();
            $item->tags->getTagIds((int) $item->id, 'com_livingword.group');
        }

        return $item;
    }

    /**
     * Load form data.
     *
     * @return  mixed
     *
     * @throws \Exception
     * @since   5.7.0
     */
    protected function loadFormData(): mixed
    {
        $data = Factory::getApplication()->getUserState('com_livingword.edit.cwmgroup.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Save the group, auto-generating invite_token if empty.
     *
     * @param   array  $data  The form data
     *
     * @return  bool  True on success
     *
     * @since   5.7.0
     */
    #[\Override]
    public function save($data): bool
    {
        if (empty($data['invite_token'])) {
            $data['invite_token'] = bin2hex(random_bytes(32));
        }

        if (!parent::save($data)) {
            return false;
        }

        return true;
    }

    /**
     * Get members for a group, joined with #__users for display name.
     *
     * @param   int  $groupId  The group ID
     *
     * @return  array  Array of member objects with name, role, joined_at
     *
     * @since   5.7.0
     */
    public function getMembers(int $groupId): array
    {
        if ($groupId <= 0) {
            return [];
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName([
                'gm.id',
                'gm.user_id',
                'gm.role',
                'gm.joined_at',
            ]))
            ->select($db->quoteName('u.name', 'user_name'))
            ->from($db->quoteName('#__livingword_group_members', 'gm'))
            ->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('gm.user_id'))
            ->where($db->quoteName('gm.group_id') . ' = ' . $groupId)
            ->order($db->quoteName('gm.joined_at') . ' ASC');

        $db->setQuery($query);

        return $db->loadObjectList() ?: [];
    }

    /**
     * Update a group member's role.
     *
     * @param   int     $memberId  The group_members row ID
     * @param   string  $role      The new role (member or leader)
     *
     * @return  bool
     *
     * @since   5.7.0
     */
    public function updateMemberRole(int $memberId, string $role): bool
    {
        $validRoles = ['member', 'leader'];
        if (!\in_array($role, $validRoles, true)) {
            $this->setError(Text::_('COM_LIVINGWORD_ERROR_INVALID_ROLE'));
            return false;
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__livingword_group_members'))
            ->set($db->quoteName('role') . ' = :role')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':role', $role)
            ->bind(':id', $memberId, \Joomla\Database\ParameterType::INTEGER);

        $db->setQuery($query);
        $db->execute();

        return true;
    }

    /**
     * @param   string  $group      The cache group
     * @param   int     $client_id  The client ID
     *
     * @return  void
     *
     * @since   5.7.0
     */
    protected function cleanCache($group = null, int $client_id = 0): void
    {
        parent::cleanCache('com_livingword');
        parent::cleanCache('mod_livingword');
    }
}
