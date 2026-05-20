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
use Joomla\CMS\Tag\TaggableTableInterface;
use Joomla\CMS\Tag\TaggableTableTrait;
use Joomla\Database\DatabaseInterface;

/**
 * Reading plan table class for #__livingword_plans
 *
 * @since  5.0.0
 */
class CwmplanTable extends Table implements TaggableTableInterface
{
    use TaggableTableTrait;

    /**
     * @var int|null
     * @since 5.0.0
     */
    public ?int $id = 0;

    /**
     * URL-safe slug (e.g. comp, newtest, bio)
     *
     * @var string|null
     * @since 5.2.0
     */
    public ?string $alias = '';

    /**
     * Display name or language key
     *
     * @var string|null
     * @since 5.2.0
     */
    public ?string $title = '';

    /**
     * Plan description
     *
     * @var string|null
     * @since 5.0.0
     */
    public ?string $description = '';

    /**
     * Introductory message (HTML or language key)
     *
     * @var string|null
     * @since 5.0.0
     */
    public ?string $message = '';

    /**
     * Enable audio player (0/1)
     *
     * @var int|null
     * @since 5.0.0
     */
    public ?int $audio = 0;

    /**
     * BibleBrain audio fileset code
     *
     * @var string|null
     * @since 5.1.0
     */
    public ?string $audio_version = '';

    /**
     * Testament scope: 0=full, 1=NT, 2=OT
     *
     * @var int|null
     * @since 5.2.0
     */
    public ?int $testament = 0;

    /**
     * Duration type: annual, fixed, or self_paced
     *
     * @var string|null
     * @since 5.7.0
     */
    public ?string $duration_type = 'annual';

    /**
     * Total days for fixed/self_paced plans
     *
     * @var int|null
     * @since 5.7.0
     */
    public ?int $total_days = null;

    /**
     * Published state
     *
     * @var int|null
     * @since 5.0.0
     */
    public ?int $published = 0;

    /**
     * @var int|null
     * @since 5.0.0
     */
    public ?int $checked_out = null;

    /**
     * @var string|null
     * @since 5.0.0
     */
    public ?string $checked_out_time = null;

    /**
     * @var int|null
     * @since 5.0.0
     */
    public ?int $ordering = 0;

    /**
     * @var string|null
     * @since 5.2.0
     */
    public ?string $created = null;

    /**
     * @var string|null
     * @since 5.2.0
     */
    public ?string $modified = null;

    /**
     * @param   DatabaseInterface  $db  Database connector object
     * @since  5.0.0
     */
    public function __construct(&$db)
    {
        $this->typeAlias = 'com_livingword.plan';

        parent::__construct('#__livingword_plans', 'id', $db);
    }

    /**
     * Get the type alias for this taggable table.
     *
     * @return  string
     *
     * @since   5.6.0
     */
    public function getTypeAlias(): string
    {
        return $this->typeAlias;
    }

    /**
     * @return  bool
     * @since   5.0.0
     */
    #[\Override]
    public function check(): bool
    {
        if (trim($this->alias ?? '') === '') {
            throw new \UnexpectedValueException('COM_LIVINGWORD_ERROR_PLAN_NAME_REQUIRED');
        }

        return parent::check();
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
        foreach (['id', 'audio', 'testament', 'published', 'checked_out', 'ordering'] as $field) {
            if (isset($src[$field])) {
                $src[$field] = $src[$field] !== '' ? (int) $src[$field] : null;
            }
        }

        return parent::bind($src, $ignore);
    }
}
