<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Administrator\Table;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;

/**
 * Plan detail (daily reading) table class for #__livingword_plans_details
 *
 * @since  5.0.0
 */
class CwmplandetailTable extends Table
{
    /**
     * @var int|null
     * @since 5.0.0
     */
    public ?int $id = 0;

    /**
     * FK to plans.id
     *
     * @var int|null
     * @since 5.2.0
     */
    public ?int $plan_id = 0;

    /**
     * Day number within the plan
     *
     * @var int|null
     * @since 5.0.0
     */
    public ?int $ordering = 0;

    /**
     * Human-readable passage reference
     *
     * @var string|null
     * @since 5.1.0
     */
    public ?string $reading = '';

    /**
     * Optional audio URL override
     *
     * @var string|null
     * @since 5.1.0
     */
    public ?string $audio = '';

    /**
     * Description text
     *
     * @var string|null
     * @since 5.0.0
     */
    public ?string $descrip = '';

    /**
     * @param   DatabaseInterface  $db  Database connector object
     * @since  5.0.0
     */
    public function __construct(&$db)
    {
        parent::__construct('#__livingword_plans_details', 'id', $db);
    }

    /**
     * @return  bool
     * @since   5.1.0
     */
    #[\Override]
    public function check(): bool
    {
        if (empty($this->plan_id)) {
            $this->setError(Text::_('COM_LIVINGWORD_ERROR_READING_PLAN_REQUIRED'));

            return false;
        }

        if (empty($this->reading)) {
            $this->setError(Text::_('COM_LIVINGWORD_ERROR_READING_REQUIRED'));

            return false;
        }

        return true;
    }

    /**
     * @param   array|object  $src     An associative array or object to bind.
     * @param   array|string  $ignore  Properties to ignore while binding.
     * @return  bool
     * @since   5.0.0
     */
    #[\Override]
    public function bind($src, $ignore = ''): bool
    {
        foreach (['id', 'plan_id', 'ordering'] as $field) {
            if (isset($src[$field])) {
                $src[$field] = $src[$field] !== '' ? (int) $src[$field] : null;
            }
        }

        return parent::bind($src, $ignore);
    }
}
