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
 * Reading plan table class for #__livingword_plans
 *
 * @since  5.0.0
 */
class CwmplanTable extends Table
{
    /** @var int|null @since 5.0.0 */
    public ?int $id = 0;

    /** @var string|null Plan identifier slug @since 5.0.0 */
    public ?string $name = '';

    /** @var string|null Plan description @since 5.0.0 */
    public ?string $description = '';

    /** @var string|null Plan message/info text @since 5.0.0 */
    public ?string $message = '';

    /** @var int|null Enable audio for this plan (0/1) @since 5.0.0 */
    public ?int $audio = 0;

    /** @var string|null BibleBrain audio translation/fileset code (e.g. ENGESV) @since 5.1.0 */
    public ?string $audio_version = '';

    /** @var int|null Testament scope: 0=full, 1=NT-only, 2=OT-only @since 5.0.0 */
    public ?int $newtest = 0;

    /** @var int|null Published state @since 5.0.0 */
    public ?int $published = 0;

    /** @var string|null @since 5.0.0 */
    public ?string $checked_out_time = null;

    /** @var int|null @since 5.0.0 */
    public ?int $checked_out = null;

    /** @var int|null @since 5.0.0 */
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
        parent::__construct('#__livingword_plans', 'id', $db);
    }

    /**
     * @return  bool
     *
     * @since   5.0.0
     */
    #[\Override]
    public function check(): bool
    {
        if (trim($this->name ?? '') === '') {
            throw new \UnexpectedValueException('COM_LIVINGWORD_ERROR_PLAN_NAME_REQUIRED');
        }

        return parent::check();
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
        foreach (['id', 'audio', 'newtest', 'published', 'checked_out', 'ordering'] as $field) {
            if (isset($src[$field])) {
                $src[$field] = $src[$field] !== '' ? (int) $src[$field] : null;
            }
        }

        return parent::bind($src, $ignore);
    }
}
