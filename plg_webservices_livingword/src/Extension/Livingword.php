<?php

/**
 * @package    Livingword.Plugin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Plugin\WebServices\Livingword\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\ApiRouter;
use Joomla\Event\SubscriberInterface;
use Joomla\Router\Route;

/**
 * Registers the LivingWord Web Services API routes.
 *
 * Two kinds of resource, with deliberately different rules.
 *
 * The **catalog** — plans, plan days, tools, links, groups — is content an
 * administrator curates. It is readable and never writable over HTTP: making
 * site content editable by API token is a different security model from the
 * ACL and edit forms the component was built around.
 *
 * The **user resources** — progress, notes, settings — belong to whoever is
 * authenticated. They are writable, because marking a reading complete is the
 * whole point of a mobile client. They are never public: `public_reads` does
 * not apply to them at all, since "readable without a token" and "scoped to
 * the current user" cannot both be true.
 *
 * @since  5.7.0
 */
class Livingword extends CMSPlugin implements SubscriberInterface
{
    /**
     * Catalog resources: resource => writable.
     *
     * All false today. The flag exists so adding a write route later is a
     * one-line change here plus a writable controller, rather than a redesign.
     *
     * @var    array<string, bool>
     * @since  5.7.0
     */
    private const CATALOG = [
        'plans'    => false,
        'plandays' => false,
        'tools'    => false,
        'links'    => false,
        'groups'   => false,
    ];

    /**
     * User-scoped resources: resource => supports a per-item route.
     *
     * `settings` is a singleton — a user has one settings record, so there is
     * no id to address and no list to page through.
     *
     * @var    array<string, bool>
     * @since  5.7.0
     */
    private const USER_RESOURCES = [
        'progress' => true,
        'notes'    => true,
        'settings' => false,
    ];

    /**
     * @return  array
     *
     * @since   5.7.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeApiRoute' => 'onBeforeApiRoute',
        ];
    }

    /**
     * Register the routes.
     *
     * This plugin being enabled IS the on/off switch; there is no separate
     * setting. Disabled, this never runs and nothing is registered.
     *
     * @param   object  $event  The event.
     *
     * @return  void
     *
     * @since   5.7.0
     */
    public function onBeforeApiRoute($event): void
    {
        $router = $event->getRouter();

        // Catalog reads require a token unless an administrator opts into
        // public reads. Safe by default: a site that enables the plugin
        // without reading anything gets an authenticated API, not an open one.
        $isPublic = (bool) $this->params->get('public_reads', 0);

        foreach (self::CATALOG as $resource => $writable) {
            $this->createReadRoutes($router, "v1/livingword/$resource", $resource, $isPublic);

            if ($writable) {
                $this->createWriteRoutes($router, "v1/livingword/$resource", $resource);
            }
        }

        foreach (self::USER_RESOURCES as $resource => $hasItemRoute) {
            $this->createUserRoutes($router, "v1/livingword/$resource", $resource, $hasItemRoute);
        }
    }

    /**
     * Catalog read routes: a list and an item.
     *
     * @param   ApiRouter  $router      The API router.
     * @param   string     $baseName    Route path.
     * @param   string     $controller  Controller name.
     * @param   bool       $isPublic    Whether reads are allowed without a token.
     *
     * @return  void
     *
     * @since   5.7.0
     */
    private function createReadRoutes(ApiRouter $router, string $baseName, string $controller, bool $isPublic): void
    {
        $defaults = [
            'component' => 'com_livingword',
            'public'    => $isPublic,
        ];

        $router->addRoutes([
            new Route(['GET'], $baseName, $controller . '.displayList', [], $defaults),
            new Route(['GET'], $baseName . '/:id', $controller . '.displayItem', ['id' => '(\d+)'], $defaults),
        ]);
    }

    /**
     * Catalog write routes. Unused today — see the CATALOG constant.
     *
     * @param   ApiRouter  $router      The API router.
     * @param   string     $baseName    Route path.
     * @param   string     $controller  Controller name.
     *
     * @return  void
     *
     * @since   5.7.0
     */
    private function createWriteRoutes(ApiRouter $router, string $baseName, string $controller): void
    {
        $defaults = [
            'component' => 'com_livingword',
            'public'    => false,
        ];

        $router->addRoutes([
            new Route(['POST'], $baseName, $controller . '.add', [], $defaults),
            new Route(['PATCH'], $baseName . '/:id', $controller . '.edit', ['id' => '(\d+)'], $defaults),
            new Route(['DELETE'], $baseName . '/:id', $controller . '.delete', ['id' => '(\d+)'], $defaults),
        ]);
    }

    /**
     * User-scoped routes. Never public, whatever `public_reads` says.
     *
     * @param   ApiRouter  $router        The API router.
     * @param   string     $baseName      Route path.
     * @param   string     $controller    Controller name.
     * @param   bool       $hasItemRoute  Whether the resource addresses items by id.
     *
     * @return  void
     *
     * @since   5.7.0
     */
    private function createUserRoutes(
        ApiRouter $router,
        string $baseName,
        string $controller,
        bool $hasItemRoute
    ): void {
        $defaults = [
            'component' => 'com_livingword',
            'public'    => false,
        ];

        $routes = [
            new Route(['GET'], $baseName, $controller . '.displayList', [], $defaults),
            new Route(['POST'], $baseName, $controller . '.add', [], $defaults),
            new Route(['PATCH'], $baseName, $controller . '.edit', [], $defaults),
        ];

        if ($hasItemRoute) {
            $routes[] = new Route(
                ['GET'],
                $baseName . '/:id',
                $controller . '.displayItem',
                ['id' => '(\d+)'],
                $defaults
            );
            $routes[] = new Route(
                ['DELETE'],
                $baseName . '/:id',
                $controller . '.delete',
                ['id' => '(\d+)'],
                $defaults
            );
        }

        $router->addRoutes($routes);
    }
}
