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

use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\CMS\Router\Exception\RouteNotFoundException;

/**
 * Base controller for resources the API exposes read-only.
 *
 * The catalog — plans, plan days, tools, links, groups — is content an
 * administrator curates in the backend. Letting it be rewritten over HTTP
 * would put site content behind an API token rather than behind the ACL and
 * the edit forms, which is a different security model than the one the
 * component was built with.
 *
 * The webservices plugin already declines to register write routes for these,
 * so this class is defence in depth: a resource cannot become writable by
 * accident if a route is added elsewhere, or if the controller is dispatched
 * directly. RouteNotFoundException is what ApiApplication turns into a 404,
 * which is the honest answer — for these resources the write route genuinely
 * does not exist.
 *
 * @since  5.7.0
 */
abstract class AbstractReadOnlyController extends ApiController
{
    /**
     * Creating is not supported for this resource.
     *
     * @return  void
     *
     * @throws  RouteNotFoundException  Always.
     *
     * @since   5.7.0
     */
    public function add()
    {
        throw new RouteNotFoundException($this->readOnlyMessage('created'));
    }

    /**
     * Editing is not supported for this resource.
     *
     * @return  void
     *
     * @throws  RouteNotFoundException  Always.
     *
     * @since   5.7.0
     */
    public function edit()
    {
        throw new RouteNotFoundException($this->readOnlyMessage('edited'));
    }

    /**
     * Deleting is not supported for this resource.
     *
     * @return  void
     *
     * @throws  RouteNotFoundException  Always.
     *
     * @since   5.7.0
     */
    public function delete($id = null)
    {
        throw new RouteNotFoundException($this->readOnlyMessage('deleted'));
    }

    /**
     * Build the message explaining why a write was refused.
     *
     * @param   string  $verb  Past-tense verb for the refused operation.
     *
     * @return  string
     *
     * @since   5.7.0
     */
    private function readOnlyMessage(string $verb): string
    {
        return \sprintf(
            'LivingWord %s are read-only over the API and cannot be %s. Manage them in the administrator.',
            $this->contentType,
            $verb
        );
    }
}
