<?php

/**
 * @package    Livingword.Module
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Module\Livingword\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmbiblegatewayHelper;
use CWM\Component\Livingword\Site\Helper\CwmreadingHelper;
use CWM\Component\Livingword\Site\Helper\CwmuserHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Registry\Registry;

/**
 * Module helper for mod_livingword
 *
 * @since  5.0.0
 */
class LivingwordHelper implements DatabaseAwareInterface
{
    use DatabaseAwareTrait;

    /**
     * Get today's reading data for the module display.
     *
     * @param   Registry  $params  Module parameters
     *
     * @return  object  Object with readingText, readingUrl, planDescription
     *
     * @throws \Exception
     * @since   5.0.0
     */
    public function getTodayReading(Registry $params): object
    {
        $db       = $this->getDatabase();
        $userId   = (int) Factory::getApplication()->getIdentity()->id;
        $userData = CwmuserHelper::getUserData($db, $userId);

        $totalDays  = CwmreadingHelper::getPlanTotalDays($db, $userData->bibleplan);
        $currentDay = CwmreadingHelper::getCurrentReadingDay($userData->startdate, (int) $userData->dateoffset, $totalDays ?: 365);
        $reading    = CwmreadingHelper::getReadingForDay($db, $userData->bibleplan, $currentDay);
        $plan       = CwmreadingHelper::getPlanByName($db, $userData->bibleplan);

        $readingText = '';
        $readingUrl  = '';

        if ($reading) {
            $readingText = CwmbiblegatewayHelper::parseReadingReference($reading->reading);
            $readingUrl  = CwmbiblegatewayHelper::buildReadingUrl($readingText, $userData->bibleversion);
        }

        return (object) [
            'readingText'     => $readingText,
            'readingUrl'      => $readingUrl,
            'planDescription' => $plan->description ?? '',
            'currentDay'      => $currentDay,
            'totalDays'       => $totalDays,
        ];
    }
}
