<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmscriptureHelper;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;

/**
 * Controller for testing audio playback from the admin plan editor.
 *
 * @since  5.4.0
 */
class CwmaudioController extends BaseController
{
    /**
     * Fetch audio URL for a given reading and version.
     *
     * @return  void
     *
     * @since   5.4.0
     */
    public function getAudio(): void
    {
        Session::checkToken('get') || jexit('Invalid Token');

        $reading = $this->input->getString('reading', '');
        $version = $this->input->getString('version', '');

        try {
            if ($reading === '' || $version === '') {
                throw new \InvalidArgumentException('Reading and version are required.');
            }

            if (!CwmscriptureHelper::isLibraryAvailable()) {
                throw new \RuntimeException('Scripture library (lib_scripture) is not installed.');
            }

            if (!CwmscriptureHelper::isAudioAvailable()) {
                throw new \RuntimeException('BibleBrain audio is not enabled. Check Scripture library settings.');
            }

            $result = CwmscriptureHelper::getAudioForReading($reading, $version);

            if ($result === null) {
                throw new \RuntimeException(
                    'No audio found for "' . $reading . '" in version "' . $version . '".'
                );
            }

            echo new JsonResponse($result);
        } catch (\Exception $e) {
            echo new JsonResponse($e);
        }

        $this->app->close();
    }
}
