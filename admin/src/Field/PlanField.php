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
 * Dropdown field listing published reading plans by alias.
 *
 * @since  5.8.0
 */
class PlanField extends ListField
{
    /** @var string @since 5.8.0 */
    protected $type = 'Plan';

    /**
     * @return  array
     *
     * @since   5.8.0
     */
    #[\Override]
    protected function getOptions(): array
    {
        $options = parent::getOptions();

        try {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true)
                ->select($db->quoteName('alias', 'value'))
                ->select($db->quoteName('title', 'text'))
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
