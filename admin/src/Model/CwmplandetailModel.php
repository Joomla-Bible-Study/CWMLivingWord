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
 * Plan detail (reading) edit model
 *
 * @since  5.0.0
 */
class CwmplandetailModel extends AdminModel
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
        $form = $this->loadForm('com_livingword.plandetail', 'reading', ['control' => 'jform', 'load_data' => $loadData]);

        if ($form === null) {
            return false;
        }

        return $form;
    }

    /**
     * @param   string  $name     The table name.
     * @param   string  $prefix   The class prefix.
     * @param   array   $options  Configuration array.
     *
     * @return  Table
     *
     * @throws  \Exception
     * @since   5.0.0
     */
    public function getTable($name = 'Cwmplandetail', $prefix = '', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    /**
     * @return  mixed
     *
     * @throws \Exception
     * @since   5.0.0
     */
    protected function loadFormData(): mixed
    {
        $data = Factory::getApplication()->getUserState('com_livingword.edit.cwmplandetail.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
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
