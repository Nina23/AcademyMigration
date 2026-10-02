<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

/**
 * TypiCMS Users module login (laravel/ui AuthenticatesUsers trait):
 * per-locale login routes, "activated" users only, custom error messages.
 */
class AuthenticationTest extends TestCase
{
    public function test_login_page_renders_in_each_locale()
    {
        foreach (['sr', 'sr-latn', 'en'] as $locale) {
            $this->get("/{$locale}/login")->assertOk()->assertSee('name="password"', false);
        }
    }

    public function test_activated_user_can_log_in_and_is_redirected_home()
    {
        $user = $this->createUser();

        $this->post('/sr/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected_with_the_password_message()
    {
        $user = $this->createUser();

        $this->from('/sr/login')
            ->post('/sr/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertRedirect('/sr/login')
            ->assertSessionHasErrors(['email' => __('The password is incorrect.', [], 'sr')]);

        $this->assertGuest();
    }

    public function test_unknown_and_non_activated_users_are_rejected_with_their_own_messages()
    {
        $inactive = $this->createUser(['activated' => 0]);

        $this->post('/sr/login', ['email' => $inactive->email, 'password' => self::PASSWORD])
            ->assertSessionHasErrors(['email' => __('This user is not activated.', [], 'sr')]);
        $this->assertGuest();

        $this->post('/sr/login', ['email' => 'nobody@example.test', 'password' => self::PASSWORD])
            ->assertSessionHasErrors(['email' => __('This user was not found.', [], 'sr')]);
        $this->assertGuest();
    }

    public function test_user_can_log_out()
    {
        $user = $this->createUser();

        $this->actingAs($user)->post('/sr/logout')->assertRedirect('/');

        $this->assertGuest();
    }
}
