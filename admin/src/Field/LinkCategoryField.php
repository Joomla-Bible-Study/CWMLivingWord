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
use Joomla\CMS\Form\Field\ComboField;
use Joomla\Database\DatabaseInterface;

/**
 * Combo field (dropdown + free text) for link categories.
 *
 * Shows existing categories from #__livingword_links as suggestions,
 * but also allows typing a new category name.
 *
 * Usage: <field name="category" type="LinkCategory" label="..." />
 *
 * @since  5.4.0
 */
class LinkCategoryField extends ComboField
{
    /**
     * @var string
     * @since 5.4.0
     */
    protected $type = 'LinkCategory';

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
