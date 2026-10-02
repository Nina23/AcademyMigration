<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * Admin area protection: "admin" middleware group (auth + verified),
 * superuser Gate::before bypass, and Spatie permission checks (can:*).
 */
class AdminAccessTest extends TestCase
{
    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/admin')->assertRedirect('/sr/login');
        $this->get('/admin/dashboard')->assertRedirect('/sr/login');
        $this->get('/admin/news')->assertRedirect('/sr/login');
    }

    public function test_superuser_can_open_the_dashboard_and_module_screens()
    {
        $this->actingAs($this->createSuperUser());

        $this->get('/admin')->assertRedirect(route('dashboard'));
        $this->get('/admin/dashboard')->assertOk();

        foreach (['/admin/news', '/admin/news/create', '/admin/pages', '/admin/files', '/admin/classschedules', '/admin/users'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_user_without_permissions_is_forbidden()
    {
        $this->actingAs($this->createUser());

        $this->get('/admin/dashboard')->assertForbidden();
        $this->get('/admin/news')->assertForbidden();
    }

    public function test_permissions_are_enforced_per_action()
    {
        $this->actingAs($this->createUserWithPermissions(['read news']));

        $this->get('/admin/news')->assertOk();
        $this->get('/admin/news/create')->assertForbidden();
        $this->get('/admin/pages')->assertForbidden();
    }
}
