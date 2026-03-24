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
    /** @var int|null @since 5.0.0 */
    public ?int $id = 0;

    /** @var string|null Plan name slug (FK to plans.name) @since 5.0.0 */
    public ?string $plan = '';

    /** @var string|null Human-readable passage reference (e.g. "Genesis 1-3; Psalm 23") @since 5.1.0 */
    public ?string $reading = '';

    /** @var string|null Optional audio reference @since 5.0.0 */
    public ?string $audio = '';

    /** @var string|null Description text @since 5.0.0 */
    public ?string $descrip = '';

    /** @var string|null @since 5.0.0 */
    public ?string $checked_out_time = null;

    /** @var int|null @since 5.0.0 */
    public ?int $checked_out = null;

    /** @var int|null Day number within the plan @since 5.0.0 */
    public ?int $ordering = 0;

    /**
     * Constructor
     *
     * @param   DatabaseInterface  $db  Database connector object
     *
     * @since  5.0.0
     */
    public function __construct(&$db)
    {
        parent::__construct('#__livingword_plans_details', 'id', $db);
    }

    /**
     * Validate before store.
     *
     * @return  bool  True if valid
     *
     * @since   5.1.0
     */
    #[\Override]
    public function check(): bool
    {
        if (empty($this->plan)) {
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
     *
     * @return  bool
     *
     * @since   5.0.0
     */
    #[\Override]
    public function bind($src, $ignore = ''): bool
    {
        foreach (['id', 'checked_out', 'ordering'] as $field) {
            if (isset($src[$field])) {
                $src[$field] = $src[$field] !== '' ? (int) $src[$field] : null;
            }
        }

        return parent::bind($src, $ignore);
    }
}
