<?php

namespace App\Routing;

use Illuminate\Routing\RouteCollection as BaseRouteCollection;

/**
 * Laravel's route collection, with one difference: when several routes share a
 * name (or a controller action), the last registered one wins, as on Laravel 8-11.
 *
 * Laravel 12 keeps the first one (inNameLookup() / inActionLookup()). Here the
 * login and password-reset POST routes both get the bare locale group name
 * ("sr::", "sr-latn::", "en::"), and Biographies registers the same names for
 * each of its pages. Registered in AppServiceProvider. Check against
 * Illuminate\Routing\RouteCollection in every Laravel upgrade (UPGRADE_PLAN.md).
 */
class RouteCollection extends BaseRouteCollection
{
    protected function inNameLookup($name)
    {
        return false;
    }

    protected function inActionLookup($controller)
    {
        return false;
    }
}
