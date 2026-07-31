<?php

/**
 * @package    Livingword.Api
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Api\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Daily readings within a reading plan, read-only.
 *
 * @since  5.7.0
 */
class PlandaysController extends AbstractReadOnlyController
{
    /**
     * @var    string
     * @since  5.7.0
     */
    protected $contentType = 'plandays';

    /**
     * @var    string
     * @since  5.7.0
     */
    protected $default_view = 'plandays';

    /**
     * Resolve the Administrator models that back this resource.
     *
     * The API reuses the backend models rather than duplicating their queries,
     * so a filter or a join fixed there is fixed here too.
     *
     * @param   string  $name    Model name.
     * @param   string  $prefix  Model prefix.
     * @param   array   $config  Model configuration.
     *
     * @return  BaseDatabaseModel
     *
     * @since   5.7.0
     */
    public function getModel($name = '', $prefix = '', $config = [])
    {
        // ApiController calls this with the contentType, not the model name, so
        // the two have to be mapped. Prefix and config are passed through
        // untouched — config carries the controller's modelState, and
        // discarding it loses the filters set before the read.
        $map = [
            'plandays' => 'Cwmplandetails',
            'planday'  => 'Cwmplandetail',
        ];

        $name = $map[strtolower($name)] ?? $name;

        return parent::getModel($name, $prefix, $config);
    }
}
