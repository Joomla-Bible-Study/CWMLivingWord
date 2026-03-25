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

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Site display controller
 *
 * @since  5.0.0
 */
class DisplayController extends BaseController
{
    /**
     * @var string
     * @since 5.0.0
     */
    protected $default_view = 'cwmhome';

    /**
     * @param   bool   $cachable   If true, the view output will be cached.
     * @param   array  $urlparams  Safe URL parameters.
     *
     * @return  static
     *
     * @throws \Exception
     * @since   5.0.0
     */
    public function display($cachable = true, $urlparams = []): static
    {
        $vName = $this->input->getCmd('view', $this->default_view);
        $this->input->set('view', $vName);

        $user = $this->app->getIdentity();

        if ($user->id) {
            $cachable = false;
        }

        $safeurlparams = [
            'id'         => 'INT',
            'group_id'   => 'INT',
            'token'      => 'CMD',
            'limit'      => 'INT',
            'limitstart' => 'INT',
            'lang'       => 'CMD',
            'Itemid'     => 'INT',
        ];

        parent::display($cachable, $safeurlparams);

        return $this;
    }
}
