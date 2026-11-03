<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Page sections in the admin (UPGRADE_PLAN O10). The page edit form lists, adds, edits and deletes
 * sections and changes their status and position through the routes pinned below. Three routes had
 * no controller method (GET admin/sections, DELETE admin/sections/{section},
 * POST admin/pages/{page}/sections/sort): unused, and a 500 when opened. They are removed.
 */
class PageSectionRoutesTest extends TestCase
{
    public function test_every_section_admin_route_has_its_controller_method()
    {
        $missing = [];
        foreach (Route::getRoutes() as $route) {
            $uses = $route->getAction('uses');
            if (is_string($uses) && str_contains($uses, 'SectionsAdminController@')) {
                [$class, $method] = explode('@', $uses);
                if (!method_exists($class, $method)) {
                    $missing[] = implode('|', $route->methods()).' '.$route->uri().' -> '.$method;
                }
            }
        }

        $this->assertSame([], $missing);
    }

    public function test_admin_sections_is_not_found_for_a_superuser_instead_of_a_server_error()
    {
        $this->actingAs($this->createSuperUser());

        $this->get('/admin/sections')->assertNotFound();
    }

    public function test_admin_sections_is_not_found_for_a_guest_instead_of_the_login_redirect()
    {
        $this->get('/admin/sections')->assertNotFound();
    }

    public function test_the_section_routes_of_the_page_form_remain()
    {
        foreach (['create-page_section', 'edit-page_section', 'store-page_section', 'update-page_section'] as $name) {
            $this->assertTrue(Route::has('admin::'.$name), $name);
        }

        $routes = Route::getRoutes();
        foreach ([
            ['GET', 'api/pages/1/sections', 'SectionsApiController@index'],
            ['PATCH', 'api/pages/1/sections/1', 'SectionsApiController@updatePartial'],
            ['DELETE', 'api/pages/1/sections/1', 'SectionsApiController@destroy'],
        ] as [$method, $uri, $action]) {
            $route = $routes->match(\Illuminate\Http\Request::create($uri, $method));
            $this->assertStringEndsWith($action, $route->getAction('uses'), "$method $uri");
        }
    }
}
