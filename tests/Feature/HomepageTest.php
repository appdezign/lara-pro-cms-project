<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The most basic contract the site has: it answers, and the unprefixed root
 * sends visitors to the default locale.
 *
 * Replaces the default Laravel ExampleTest, which asserted that "/" returns 200
 * and so had always failed against this application.
 *
 * NOTE: frontend *page rendering* cannot be tested here yet. BaseFrontController
 * and its siblings do all their setup inside `if (!App::runningInConsole())`,
 * which is false under PHPUnit, leaving their typed properties uninitialised -
 * any front controller then fails with "Typed property ...::$routename must not
 * be accessed before initialization". Changing that guard to something like
 * `Route::current() !== null` would unlock frontend feature tests; it touches
 * every front controller, so it is not done here.
 *
 * The two cases below are reachable because neither goes through a front
 * controller: the root redirect is handled by the locale middleware, and the
 * admin panel is Filament.
 */
class HomepageTest extends TestCase
{
    public function test_the_root_redirects_to_the_default_locale(): void
    {
        $locale = config('app.locale');

        $this->get('/')
            ->assertRedirect()
            ->assertRedirectContains('/'.$locale);
    }

    public function test_the_admin_login_page_renders(): void
    {
        $this->get('/admin/login')->assertOk();
    }
}
