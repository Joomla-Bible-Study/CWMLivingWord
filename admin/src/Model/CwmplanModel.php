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

        if (\is_object($data) && !empty($data->name)) {
            $data->readings = $this->getReadingsForPlan($data->name);
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
     * @since   5.1.0
     */
    #[\Override]
    public function save($data): bool
    {
        $readings = $data['readings'] ?? [];
        unset($data['readings']);

        // Get old plan name before save (for FK update if name changed)
        $oldName = '';

        if (!empty($data['id'])) {
            $existing = $this->getItem($data['id']);
            $oldName  = $existing->name ?? '';
        }

        if (!parent::save($data)) {
            return false;
        }

        $planName = $data['name'];

        $this->syncReadings($oldName ?: $planName, $planName, $readings);

        return true;
    }

    /**
     * Load all readings for a plan as an array suitable for the subform field.
     *
     * @param   string  $planName  Plan name slug
     *
     * @return  array  Array of reading row arrays
     *
     * @since   5.1.0
     */
    public function getReadingsForPlan(string $planName): array
    {
        if (empty($planName)) {
            return [];
        }

        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['reading', 'audio', 'descrip']))
            ->from($db->quoteName('#__livingword_plans_details'))
            ->where($db->quoteName('plan') . ' = ' . $db->quote($planName))
            ->order($db->quoteName('ordering') . ' ASC');

        $db->setQuery($query);

        return $db->loadAssocList() ?: [];
    }

    /**
     * Sync readings from the subform data to the plans_details table.
     *
     * Deletes all existing readings for the old plan name, then inserts
     * the new readings with ordering derived from array position.
     *
     * @param   string  $oldPlanName  Previous plan name (for deletion)
     * @param   string  $newPlanName  Current plan name (for insertion)
     * @param   array   $readings     Array of reading row data from subform
     *
     * @return  void
     *
     * @since   5.1.0
     */
    private function syncReadings(string $oldPlanName, string $newPlanName, array $readings): void
    {
        $db = $this->getDatabase();

        // Delete existing readings
        if (!empty($oldPlanName)) {
            $query = $db->getQuery(true)
                ->delete($db->quoteName('#__livingword_plans_details'))
                ->where($db->quoteName('plan') . ' = ' . $db->quote($oldPlanName));

            $db->setQuery($query);
            $db->execute();
        }

        // Insert new readings
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
                'plan'     => $newPlanName,
                'reading'  => $reading,
                'audio'    => trim($row['audio'] ?? ''),
                'descrip'  => trim($row['descrip'] ?? ''),
                'ordering' => $ordering,
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
