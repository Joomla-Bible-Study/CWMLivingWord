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
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Exception\RouteNotFoundException;

/**
 * Base controller for resources that belong to the authenticated user.
 *
 * Progress, notes and settings are personal records — what someone has read,
 * what they wrote about it, when they want their email. The entire security
 * property of these endpoints is that a request can only ever reach the
 * requester's own rows.
 *
 * That is enforced in one place: {@see self::userId()} reads the identity from
 * the application and nothing else. No route carries a user id, no method
 * accepts one, and any `user_id` a client sends in a body or query string is
 * ignored — it is never read, so it cannot be honoured by accident.
 *
 * The models apply the scope themselves and default to user 0, which matches
 * nobody. So the failure mode of a controller that forgets to set the scope is
 * an empty result, not somebody else's reading history.
 *
 * @since  5.7.0
 */
abstract class AbstractUserScopedController extends ApiController
{
    /**
     * Administrator model backing this resource, e.g. 'Cwmprogress'.
     *
     * Declared here because ApiController has no such property — it resolves
     * models from the view name, and these resources do not share a name with
     * their model.
     *
     * @var    string
     * @since  5.7.0
     */
    protected $modelName = '';

    /**
     * The authenticated user's id.
     *
     * @return  int
     *
     * @throws  RouteNotFoundException  When there is no authenticated user.
     *
     * @since   5.7.0
     */
    protected function userId(): int
    {
        $identity = $this->app->getIdentity();
        $id       = $identity ? (int) $identity->id : 0;

        if ($id < 1) {
            // The routes are registered with public => false, so the API
            // application should have rejected this already. Refusing here too
            // means a misconfigured route cannot turn a personal resource into
            // an anonymous one.
            throw new RouteNotFoundException(
                'LivingWord user data requires an authenticated user.'
            );
        }

        return $id;
    }

    /**
     * Apply the user scope to the model state before a read.
     *
     * @return  void
     *
     * @since   5.7.0
     */
    protected function scopeToUser(): void
    {
        $this->modelState->set('filter.user_id', $this->userId());
    }

    /**
     * List the current user's records.
     *
     * @return  static
     *
     * @since   5.7.0
     */
    public function displayList()
    {
        $this->scopeToUser();

        return parent::displayList();
    }

    /**
     * Show one of the current user's records.
     *
     * @param   integer  $id  The record id.
     *
     * @return  static
     *
     * @since   5.7.0
     */
    public function displayItem($id = null)
    {
        $this->scopeToUser();

        return parent::displayItem($id);
    }

    /**
     * Writes are implemented per resource, not inherited.
     *
     * ApiController::add() and ::edit() drive an AdminModel's save(), which
     * expects a form, a table and a bind/validate cycle. These resources are
     * backed by ListModels over rows a user accumulates by reading, and their
     * write semantics are not "save a record" — marking a day complete is
     * idempotent, and saving an empty note deletes it. So each controller
     * implements its own, and anything that has not is refused rather than
     * fatally calling save() on a model that does not have one.
     *
     * @return  void
     *
     * @throws  RouteNotFoundException  Unless a subclass overrides this.
     *
     * @since   5.7.0
     */
    public function add()
    {
        throw new RouteNotFoundException(
            \sprintf('Creating LivingWord %s over the API is not supported.', $this->contentType)
        );
    }

    /**
     * @return  void
     *
     * @throws  RouteNotFoundException  Unless a subclass overrides this.
     *
     * @since   5.7.0
     */
    public function edit()
    {
        throw new RouteNotFoundException(
            \sprintf('Editing LivingWord %s over the API is not supported.', $this->contentType)
        );
    }

    /**
     * @param   integer  $id  The record id.
     *
     * @return  void
     *
     * @throws  RouteNotFoundException  Unless a subclass overrides this.
     *
     * @since   5.7.0
     */
    public function delete($id = null)
    {
        throw new RouteNotFoundException(
            \sprintf('Deleting LivingWord %s over the API is not supported.', $this->contentType)
        );
    }

    /**
     * Read a positive integer from the request body or query string.
     *
     * @param   string  $key  Field name.
     *
     * @return  int  Zero when absent or not positive.
     *
     * @since   5.7.0
     */
    protected function requestInt(string $key): int
    {
        $body = (array) $this->input->json->get('data', [], 'array');

        if (isset($body['attributes'][$key])) {
            return (int) $body['attributes'][$key];
        }

        if (isset($body[$key])) {
            return (int) $body[$key];
        }

        return $this->input->getInt($key, 0);
    }

    /**
     * Resolve the Administrator model backing this resource.
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
        if ($name === '') {
            $name = $this->modelName;
        }

        return parent::getModel($name, 'Administrator', ['ignore_request' => true]);
    }
}
