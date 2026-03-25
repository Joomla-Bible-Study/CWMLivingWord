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

use Joomla\CMS\Form\Field\ListField;

/**
 * Dropdown field listing common Bible translation codes.
 *
 * @since  5.8.0
 */
class BibleVersionField extends ListField
{
    /** @var string @since 5.8.0 */
    protected $type = 'BibleVersion';

    /**
     * Common Bible translations used with BibleGateway and BibleBrain.
     *
     * @var array
     * @since 5.8.0
     */
    private const VERSIONS = [
        'kjv'  => 'King James Version (KJV)',
        'nkjv' => 'New King James Version (NKJV)',
        'niv'  => 'New International Version (NIV)',
        'esv'  => 'English Standard Version (ESV)',
        'nlt'  => 'New Living Translation (NLT)',
        'nasb' => 'New American Standard Bible (NASB)',
        'amp'  => 'Amplified Bible (AMP)',
        'csb'  => 'Christian Standard Bible (CSB)',
        'web'  => 'World English Bible (WEB)',
        'msg'  => 'The Message (MSG)',
    ];

    /**
     * @return  array
     *
     * @since   5.8.0
     */
    #[\Override]
    protected function getOptions(): array
    {
        $options = parent::getOptions();

        foreach (self::VERSIONS as $code => $label) {
            $options[] = (object) ['value' => $code, 'text' => $label];
        }

        return $options;
    }
}
