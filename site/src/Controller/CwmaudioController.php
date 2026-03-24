<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmscriptureHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

/**
 * Audio AJAX controller — returns audio URLs for scripture passages.
 *
 * Keeps the Bible Brain API key server-side by resolving audio URLs
 * and returning them as JSON to the frontend player.
 *
 * @since  5.2.0
 */
class CwmaudioController extends BaseController
{
    /**
     * Get audio URLs for a reading reference.
     *
     * Request params:
     *   - reading: Scripture reference (e.g. "Genesis 1-3; Psalm 23")
     *   - version: Bible translation code (e.g. "kjv")
     *
     * @return  void
     *
     * @since   5.2.0
     */
    public function getAudio(): void
    {
        if (!Session::checkToken('get')) {
            $this->app->setHeader('Content-Type', 'application/json; charset=utf-8');
            $this->app->sendHeaders();
            echo new JsonResponse(null, 'Invalid token', true);
            $this->app->close();
        }

        $reading = $this->input->getString('reading', '');
        $version = $this->input->getCmd('version', 'kjv');

        if (empty($reading)) {
            $this->app->setHeader('Content-Type', 'application/json; charset=utf-8');
            $this->app->sendHeaders();
            echo new JsonResponse(null, 'No reading reference provided', true);
            $this->app->close();
        }

        $audioData = CwmscriptureHelper::getAudioForReading($reading, $version);

        if ($audioData === null) {
            $this->app->setHeader('Content-Type', 'application/json; charset=utf-8');
            $this->app->sendHeaders();
            echo new JsonResponse(null, 'Audio not available for this passage', true);
            $this->app->close();
        }

        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8');
        $this->app->sendHeaders();
        echo new JsonResponse($audioData);
        $this->app->close();
    }
}