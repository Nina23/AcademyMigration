<?php

namespace Tests\Feature\Boot;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Route names registered more than once: the login and password-reset POST
 * routes both get the bare locale group name ("sr::", "sr-latn::", "en::").
 * Laravel 8–11 resolve a duplicated name to the last registered route;
 * Laravel 12 would use the first.
 */
class RouteNameTest extends TestCase
{
    public function test_a_duplicated_route_name_resolves_to_the_last_registered_route()
    {
        foreach (['sr', 'sr-latn', 'en'] as $locale) {
            $route = Route::getRoutes()->getByName($locale.'::');

            $this->assertSame($locale.'/password/reset', $route->uri(), "{$locale}::");
            $this->assertSame(['POST'], $route->methods(), "{$locale}::");
        }
    }
}
