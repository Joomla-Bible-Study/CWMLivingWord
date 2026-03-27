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

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;

/**
 * Group reading plan table class for #__livingword_groups
 *
 * @since  5.7.0
 */
class CwmgroupTable extends Table
{
    /**
     * @var int|null
     * @since 5.7.0
     */
    public ?int $id = 0;

    /**
     * Group name
     *
     * @var string|null
     * @since 5.7.0
     */
    public ?string $name = '';

    /**
     * Group description
     *
     * @var string|null
     * @since 5.7.0
     */
    public ?string $description = '';

    /**
     * FK to #__livingword_plans
     *
     * @var int|null
     * @since 5.7.0
     */
    public ?int $plan_id = 0;

    /**
     * Start date for the group plan
     *
     * @var string|null
     * @since 5.7.0
     */
    public ?string $start_date = null;

    /**
     * Invite token for joining the group
     *
     * @var string|null
     * @since 5.7.0
     */
    public ?string $invite_token = '';

    /**
     * Join mode: open, request, or private
     *
     * @var string|null
     * @since 5.7.0
     */
    public ?string $join_mode = 'open';

    /**
     * User ID of group creator
     *
     * @var int|null
     * @since 5.7.0
     */
    public ?int $created_by = 0;

    /**
     * Published state
     *
     * @var int|null
     * @since 5.7.0
     */
    public ?int $published = 0;

    /**
     * @var int|null
     * @since 5.7.0
     */
    public ?int $checked_out = null;

    /**
     * @var string|null
     * @since 5.7.0
     */
    public ?string $checked_out_time = null;

    /**
     * @var int|null
     * @since 5.7.0
     */
    public ?int $ordering = 0;

    /**
     * @var string|null
     * @since 5.7.0
     */
    public ?string $created = null;

    /**
     * @var string|null
     * @since 5.7.0
     */
    public ?string $modified = null;

    /**
     * @param   DatabaseInterface  $db  Database connector object
     * @since  5.7.0
     */
    public function __construct(&$db)
    {
        parent::__construct('#__livingword_groups', 'id', $db);
    }

    /**
     * @return  bool
     * @since   5.7.0
     */
    #[\Override]
    public function check(): bool
    {
        if (trim($this->name ?? '') === '') {
            throw new \UnexpectedValueException('COM_LIVINGWORD_ERROR_GROUP_NAME_REQUIRED');
        }

        return parent::check();
    }

    /**
     * @param   array|object  $src     An associative array or object to bind.
     * @param   array|string  $ignore  Properties to ignore while binding.
     * @return  bool
     * @since   5.7.0
     */
    #[\Override]
    public function bind($src, $ignore = ''): bool
    {
        foreach (['id', 'plan_id', 'created_by', 'published', 'checked_out', 'ordering'] as $field) {
            if (isset($src[$field])) {
                $src[$field] = $src[$field] !== '' ? (int) $src[$field] : null;
            }
        }

        return parent::bind($src, $ignore);
    }
}
