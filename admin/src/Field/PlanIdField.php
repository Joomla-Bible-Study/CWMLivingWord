<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Administrator\Field;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\Database\DatabaseInterface;

/**
 * Dropdown field listing published reading plans by ID.
 *
 * Use in forms where the value stored is the plan's integer ID.
 * For forms that store the plan alias, use the Plan field type instead.
 *
 * Usage: <field name="plan_id" type="PlanId" label="..." />
 *
 * @since  5.4.0
 */
class PlanIdField extends ListField
{
    /**
     * @var string
     * @since 5.4.0
     */
    protected $type = 'PlanId';

    /**
     * @return  array
     *
     * @since   5.4.0
     */
    #[\Override]
    protected function getOptions(): array
    {
        $options = parent::getOptions();

        try {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select([$db->quoteName('id', 'value'), $db->quoteName('title', 'text')])
                ->from($db->quoteName('#__livingword_plans'))
                ->where($db->quoteName('published') . ' = 1')
                ->order($db->quoteName('ordering') . ' ASC');
            $db->setQuery($query);
            $plans = $db->loadObjectList() ?: [];

            foreach ($plans as $plan) {
                $options[] = (object) ['value' => $plan->value, 'text' => $plan->text];
            }
        } catch (\Exception) {
            // Table may not exist yet during install
        }

        return $options;
    }
}
