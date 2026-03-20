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
 * User settings table class for #__livingword
 *
 * @since  5.0.0
 */
class CwmuserTable extends Table
{
    /** @var int|null @since 5.0.0 */
    public ?int $id = 0;

    /** @var int|null Joomla user ID @since 5.0.0 */
    public ?int $userid = 0;

    /** @var string|null Selected reading plan name @since 5.0.0 */
    public ?string $bibleplan = '';

    /** @var string|null Selected Bible version code @since 5.0.0 */
    public ?string $bibleversion = '';

    /** @var string|null Parallel Bible version code @since 5.0.0 */
    public ?string $pbversion = '';

    /** @var string|null Audio version code @since 5.0.0 */
    public ?string $audioversion = '';

    /** @var int|null Email subscription flag @since 5.0.0 */
    public ?int $email = 0;

    /** @var int|null Plan view layout preference @since 5.0.0 */
    public ?int $planview = 0;

    /** @var int|null Reading state @since 5.0.0 */
    public ?int $readstate = 0;

    /** @var string|null Plan start date @since 5.0.0 */
    public ?string $startdate = null;

    /** @var int|null Date offset in days @since 5.0.0 */
    public ?int $dateoffset = 0;

    /**
     * Constructor
     *
     * @param   DatabaseInterface  $db  Database connector object
     *
     * @since  5.0.0
     */
    public function __construct(&$db)
    {
        parent::__construct('#__livingword', 'id', $db);
    }

    /**
     * @return  bool
     *
     * @since   5.0.0
     */
    #[\Override]
    public function check(): bool
    {
        if (empty($this->startdate) || $this->startdate === '0000-00-00') {
            $this->startdate = null;
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
        foreach (['id', 'userid', 'email', 'planview', 'readstate', 'dateoffset'] as $field) {
            if (isset($src[$field])) {
                $src[$field] = $src[$field] !== '' ? (int) $src[$field] : null;
            }
        }

        return parent::bind($src, $ignore);
    }
}
