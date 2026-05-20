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
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

/**
 * Plan edit model
 *
 * @since  5.0.0
 */
class CwmplanModel extends AdminModel
{
    /**
     * @param   array  $data      Data for the form.
     * @param   bool   $loadData  True if the form is to load its own data.
     *
     * @return  mixed  A JForm object on success, false on failure
     *
     * @throws \Exception
     * @since 5.0.0
     */
    public function getForm($data = [], $loadData = true): mixed
    {
        $form = $this->loadForm('com_livingword.plan', 'plan', ['control' => 'jform', 'load_data' => $loadData]);

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
     * @since   5.0.0
     */
    public function getTable($name = 'Cwmplan', $prefix = '', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * Load a single plan and attach its current tag IDs so the edit form's
     * tag field pre-populates. AdminModel::save() handles tag persistence
     * automatically via plg_behaviour_taggable.
     *
     * @param   int|null  $pk  The id of the row to fetch.
     *
     * @return  mixed  Plan object on success, false on failure.
     *
     * @since   5.6.0
     */
    #[\Override]
    public function getItem($pk = null): mixed
    {
        $item = parent::getItem($pk);

        if ($item && !empty($item->id)) {
            $item->tags = new TagsHelper();
            $item->tags->getTagIds((int) $item->id, 'com_livingword.plan');
        }

        return $item;
    }

    /**
     * Load form data, injecting readings from the plans_details table.
     *
     * @return  mixed
     *
     * @throws \Exception
     * @since   5.0.0
     */
    protected function loadFormData(): mixed
    {
        $data = Factory::getApplication()->getUserState('com_livingword.edit.cwmplan.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        if (\is_object($data) && !empty($data->id)) {
            $data->readings = $this->getReadingsForPlan((int) $data->id);
        }

        return $data;
    }

    /**
     * Save the plan and sync its readings to the plans_details table.
     *
     * @param   array  $data  The form data
     *
     * @return  bool  True on success
     *
     * @throws \Exception
     * @since   5.1.0
     */
    #[\Override]
    public function save($data): bool
    {
        $readings = $data['readings'] ?? [];
        unset($data['readings']);

        if (!parent::save($data)) {
            return false;
        }

        $planId = (int) $this->getState($this->getName() . '.id');

        $this->syncReadings($planId, $readings);

        return true;
    }

    /**
     * Load all readings for a plan as an array suitable for the subform field.
     *
     * @param   int  $planId  Plan ID
     *
     * @return  array  Array of reading row arrays
     *
     * @since   5.1.0
     */
    public function getReadingsForPlan(int $planId): array
    {
        if ($planId <= 0) {
            return [];
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['reading', 'audio', 'descrip']))
            ->from($db->quoteName('#__livingword_plans_details'))
            ->where($db->quoteName('plan_id') . ' = ' . $planId)
            ->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);

        return $db->loadAssocList() ?: [];
    }

    /**
     * Sync readings from the subform data to the plans_details table.
     *
     * @param   int    $planId    Plan ID
     * @param   array  $readings  Array of reading row data from subform
     *
     * @return  void
     *
     * @since   5.1.0
     */
    private function syncReadings(int $planId, array $readings): void
    {
        $db = $this->getDatabase();

        // Delete existing readings
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__livingword_plans_details'))
            ->where($db->quoteName('plan_id') . ' = ' . $planId);

        $db->setQuery($query);
        $db->execute();

        if (empty($readings)) {
            return;
        }

        $ordering = 1;

        foreach ($readings as $row) {
            $reading = trim($row['reading'] ?? '');

            if (empty($reading)) {
                continue;
            }

            $record = (object) [
                'plan_id'  => $planId,
                'ordering' => $ordering,
                'reading'  => $reading,
                'audio'    => trim($row['audio'] ?? ''),
                'descrip'  => trim($row['descrip'] ?? ''),
            ];

            $db->insertObject('#__livingword_plans_details', $record);
            $ordering++;
        }
    }

    /**
     * @param   string  $group      The cache group
     * @param   int     $client_id  The client ID
     *
     * @return  void
     *
     * @since   5.0.0
     */
    protected function cleanCache($group = null, int $client_id = 0): void
    {
        parent::cleanCache('com_livingword');
        parent::cleanCache('mod_livingword');
    }
}
