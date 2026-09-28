<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Lara\Common\Models\User;
use Tests\TestCase;

/**
 * The cache maintenance endpoints rebuild the config, event, view and route
 * caches. They used to be unauthenticated GET routes; these tests pin down the
 * protection so it cannot be removed by accident.
 *
 * Note: this suite currently runs against the local environment and database
 * (phpunit.xml sets APP_ENV=testing but it is not taking effect), so the tests
 * below avoid anything that actually clears a cache.
 */
class LaraCacheEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // CSRF is not auto-bypassed here because the suite runs as "local";
        // disable it explicitly so these tests exercise the auth boundary.
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_cache_endpoints_are_not_reachable_by_guests(): void
    {
        $this->post(route('laracache.clear'))->assertRedirect();
        $this->post(route('laracache.cache'))->assertRedirect();
        $this->post(route('special.cache.clear'))->assertRedirect();
    }

    public function test_cache_endpoints_do_not_respond_to_get(): void
    {
        // the action must not run; the locale catch-all handles the stray GET
        $this->get('/laracache/clear')->assertRedirect();
        $this->get('/laracache/cache')->assertRedirect();
    }

    public function test_clear_is_reachable_for_an_authenticated_panel_user(): void
    {
        $user = User::where('name', 'admin')->first();

        if (! $user) {
            $this->markTestSkipped('No "admin" user in the current database.');
        }

        // no laracacheclear session key, so nothing is actually cleared
        $this->actingAs($user)
            ->post(route('laracache.clear'))
            ->assertOk()
            ->assertJson(['success' => true]);
    }
}
