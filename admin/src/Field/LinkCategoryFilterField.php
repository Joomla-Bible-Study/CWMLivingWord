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
 * List field for filtering links by category.
 *
 * Usage: <field name="category" type="LinkCategoryFilter" label="..." />
 *
 * @since  5.4.0
 */
class LinkCategoryFilterField extends ListField
{
    /**
     * @var string
     * @since 5.4.0
     */
    protected $type = 'LinkCategoryFilter';

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
                ->select('DISTINCT ' . $db->quoteName('category'))
                ->from($db->quoteName('#__livingword_links'))
                ->where($db->quoteName('category') . ' != ' . $db->quote(''))
                ->order($db->quoteName('category') . ' ASC');
            $db->setQuery($query);
            $categories = $db->loadColumn() ?: [];

            foreach ($categories as $cat) {
                $options[] = (object) ['value' => $cat, 'text' => $cat];
            }
        } catch (\Exception) {
            // Table may not exist yet during install
        }

        return $options;
    }
}
