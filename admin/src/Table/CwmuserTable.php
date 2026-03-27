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
 * User settings table class for #__livingword_users
 *
 * @since  5.0.0
 */
class CwmuserTable extends Table
{
    /**
     * @var int|null
     * @since 5.0.0
     */
    public ?int $id = 0;

    /**
     * Joomla user ID
     *
     * @var int|null
     * @since 5.2.0
     */
    public ?int $user_id = 0;

    /**
     * FK to plans.id
     *
     * @var int|null
     * @since 5.2.0
     */
    public ?int $plan_id = 0;

    /**
     * Bible translation code
     *
     * @var string|null
     * @since 5.2.0
     */
    public ?string $bible_version = '';

    /**
     * Preferred audio Bible fileset code
     *
     * @var string|null
     * @since 5.7.0
     */
    public ?string $audio_version = '';

    /**
     * Email subscription flag (0/1)
     *
     * @var int|null
     * @since 5.0.0
     */
    public ?int $email = 0;

    /**
     * One-click unsubscribe token
     *
     * @var string|null
     * @since 5.2.0
     */
    public ?string $unsubscribe_token = null;

    /**
     * Plan view preference (0=default, 1=list, 2=calendar)
     *
     * @var int|null
     * @since 5.2.0
     */
    public ?int $plan_view = 0;

    /**
     * Plan start date
     *
     * @var string|null
     * @since 5.2.0
     */
    public ?string $start_date = null;

    /**
     * Date offset in days
     *
     * @var int|null
     * @since 5.2.0
     */
    public ?int $date_offset = 0;

    /**
     * Current consecutive days streak
     *
     * @var int|null
     * @since 5.4.0
     */
    public ?int $streak_current = 0;

    /**
     * Best streak ever
     *
     * @var int|null
     * @since 5.4.0
     */
    public ?int $streak_best = 0;

    /**
     * Last date a reading was completed
     *
     * @var string|null
     * @since 5.4.0
     */
    public ?string $streak_last_date = null;

    /**
     * Preferred email hour (0-23)
     *
     * @var int|null
     * @since 5.8.0
     */
    public ?int $email_hour = 6;

    /**
     * User timezone
     *
     * @var string|null
     * @since 5.8.0
     */
    public ?string $timezone = '';

    /**
     * FK to #__users.id — accountability partner
     *
     * @var int|null
     * @since 5.6.0
     */
    public ?int $accountability_partner_id = null;

    /**
     * Share progress with partner (0/1)
     *
     * @var int|null
     * @since 5.6.0
     */
    public ?int $share_progress = 0;

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
        parent::__construct('#__livingword_users', 'id', $db);
    }

    /**
     * @return  bool
     * @since   5.0.0
     */
    #[\Override]
    public function check(): bool
    {
        if (empty($this->start_date) || $this->start_date === '0000-00-00') {
            $this->start_date = null;
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
        foreach (['id', 'user_id', 'plan_id', 'email', 'plan_view', 'date_offset', 'email_hour', 'accountability_partner_id', 'share_progress'] as $field) {
            if (isset($src[$field])) {
                $src[$field] = $src[$field] !== '' ? (int) $src[$field] : null;
            }
        }

        return parent::bind($src, $ignore);
    }
}
