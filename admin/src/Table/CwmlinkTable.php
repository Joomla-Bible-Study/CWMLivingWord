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
 * Resource links table class for #__livingword_links
 *
 * @since  5.0.0
 */
class CwmlinkTable extends Table
{
    /**
     * @var int|null
     * @since 5.0.0
     */
    public ?int $id = 0;

    /**
     * Link display name
     *
     * @var string|null
     * @since 5.0.0
     */
    public ?string $name = '';

    /**
     * Link URL
     *
     * @var string|null
     * @since 5.0.0
     */
    public ?string $url = '';

    /**
     * Link category
     *
     * @var string|null
     * @since 5.0.0
     */
    public ?string $category = '';

    /**
     * Target window (0=same, 2=new)
     *
     * @var int|null
     * @since 5.0.0
     */
    public ?int $target = 0;

    /**
     * Published state
     *
     * @var int|null
     * @since 5.0.0
     */
    public ?int $published = 0;

    /**
     * @var string|null
     * @since 5.0.0
     */
    public ?string $checked_out_time = null;

    /**
     * @var int|null
     * @since 5.0.0
     */
    public ?int $checked_out = null;

    /**
     * @var int|null
     * @since 5.0.0
     */
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
        parent::__construct('#__livingword_links', 'id', $db);
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
            throw new \UnexpectedValueException('COM_LIVINGWORD_ERROR_LINK_NAME_REQUIRED');
        }

        if (trim($this->url ?? '') === '') {
            throw new \UnexpectedValueException('COM_LIVINGWORD_ERROR_LINK_URL_REQUIRED');
        }

        if (!empty($this->url) && !preg_match('#^[a-z][a-z0-9+\-.]*://#i', $this->url)) {
            $this->url = 'https://' . $this->url;
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
        foreach (['id', 'target', 'published', 'checked_out', 'ordering'] as $field) {
            if (isset($src[$field])) {
                $src[$field] = $src[$field] !== '' ? (int) $src[$field] : null;
            }
        }

        return parent::bind($src, $ignore);
    }
}
