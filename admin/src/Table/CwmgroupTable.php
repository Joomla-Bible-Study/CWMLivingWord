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
    /** @var int|null @since 5.7.0 */
    public ?int $id = 0;

    /** @var string|null Group name @since 5.7.0 */
    public ?string $name = '';

    /** @var string|null Group description @since 5.7.0 */
    public ?string $description = '';

    /** @var int|null FK to #__livingword_plans @since 5.7.0 */
    public ?int $plan_id = 0;

    /** @var string|null Start date for the group plan @since 5.7.0 */
    public ?string $start_date = null;

    /** @var string|null Invite token for joining the group @since 5.7.0 */
    public ?string $invite_token = '';

    /** @var string|null Join mode: open, request, or private @since 5.7.0 */
    public ?string $join_mode = 'open';

    /** @var int|null User ID of group creator @since 5.7.0 */
    public ?int $created_by = 0;

    /** @var int|null Published state @since 5.7.0 */
    public ?int $published = 0;

    /** @var int|null @since 5.7.0 */
    public ?int $checked_out = null;

    /** @var string|null @since 5.7.0 */
    public ?string $checked_out_time = null;

    /** @var int|null @since 5.7.0 */
    public ?int $ordering = 0;

    /** @var string|null @since 5.7.0 */
    public ?string $created = null;

    /** @var string|null @since 5.7.0 */
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
