<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Lara\Common\Models\MenuItem;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Tests\TestCase;

/**
 * Renders the public site through its real routes.
 *
 * Until now the frontend could not be tested at all: BaseFrontController and
 * its siblings did all their setup inside `if (!App::runningInConsole())`,
 * which is false under PHPUnit, so every front controller died on an
 * uninitialised typed property. The guard now asks whether there is a matched
 * route, which is what it actually meant.
 *
 * Two things make frontend requests work in a test:
 *
 *  - The routing locale has to be forced. Routes are registered inside a group
 *    prefixed with LaravelLocalization::setLocale(), which returns nothing
 *    outside a real request, so without this the table has no locale prefix and
 *    every localised URL falls through to the catch-all. This is the same
 *    mechanism lara:route:cache uses to build one cache file per locale.
 *
 *  - Every assertion has to check which route actually matched. The CMS renders
 *    its error page with a 200 status, so "assertOk()" alone passes even when
 *    the request quietly landed on the 404 catch-all.
 */
class FrontendRenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv(LaravelLocalization::ENV_ROUTE_KEY . '=' . config('app.locale'));

        $this->refreshApplication();
    }

    protected function tearDown(): void
    {
        putenv(LaravelLocalization::ENV_ROUTE_KEY . '=');

        parent::tearDown();
    }

    private function locale(): string
    {
        return config('app.locale');
    }

    /**
     * Request a path and assert it rendered the route we expected, not the
     * error page wearing a 200.
     */
    private function assertRenders(string $path, string $expectedRoutePrefix = ''): void
    {
        $response = $this->get($path);
        $matched = Route::current()?->getName() ?? '(none)';

        $this->assertSame(
            200,
            $response->status(),
            $path . ' did not render (matched route: ' . $matched . ').'
        );

        $this->assertNotSame(
            'error.show.404',
            $matched,
            $path . ' fell through to the error page instead of its own route.'
        );

        if ($expectedRoutePrefix !== '') {
            $this->assertStringStartsWith($expectedRoutePrefix, $matched, $path . ' matched an unexpected route.');
        }
    }

    public function test_the_home_page_renders(): void
    {
        $this->assertRenders('/' . $this->locale(), 'special.home.show');
    }

    /**
     * Every published, routable menu item must render its own page.
     */
    public function test_every_menu_page_renders(): void
    {
        $locale = $this->locale();

        $menuItems = MenuItem::langIs($locale)
            ->whereIn('type', ['page', 'entity', 'form'])
            ->whereNotNull('route')
            ->whereNotNull('routename')
            ->with('entity')
            ->get()
            ->filter(fn(MenuItem $item): bool => $item->entity !== null && Route::has($item->routename));

        $this->assertGreaterThan(0, $menuItems->count(), 'No routable menu items to render.');

        $failures = [];

        $checked = 0;

        foreach ($menuItems as $item) {
            // a page behind auth correctly redirects a guest to the login form
            $needsAuth = $item->route_has_auth || $item->entity->has_front_auth;

            $response = $this->get('/' . $locale . '/' . $item->route);
            $matched = Route::current()?->getName() ?? '(none)';
            $checked++;

            if ($needsAuth) {
                if ($response->status() !== 302) {
                    $failures[] = $item->route . ' => expected a redirect to login, got HTTP ' . $response->status();
                }

                continue;
            }

            if ($response->status() !== 200) {
                $failures[] = $item->route . ' => HTTP ' . $response->status();
            } elseif ($matched === 'error.show.404') {
                $failures[] = $item->route . ' => fell through to the error page';
            }
        }

        fwrite(STDERR, "\n  rendered {$checked} menu pages\n");

        $this->assertSame([], $failures, 'Menu pages that did not render.');
    }

    /**
     * A single content object, reached through its menu-driven SEO route.
     */
    public function test_a_single_entity_object_renders(): void
    {
        $locale = $this->locale();

        $listItem = MenuItem::langIs($locale)
            ->typeIs('entity')
            ->whereNotNull('route')
            ->whereNull('tag_id')
            ->with('entity')
            ->get()
            ->first(fn(MenuItem $item): bool => $item->entity !== null
                && Route::has($item->routename . '.show'));

        if (!$listItem) {
            $this->markTestSkipped('No entity menu item with a single-object route.');
        }

        $modelClass = $listItem->entity->model_class;

        if (!$modelClass || !class_exists($modelClass)) {
            $this->markTestSkipped('Entity has no usable model class.');
        }

        $object = $modelClass::query()->whereNotNull('slug')->first();

        if (!$object) {
            $this->markTestSkipped('No published object to render.');
        }

        $suffix = $listItem->entity->objrel_has_terms ? '.html' : '';

        $this->assertRenders('/' . $locale . '/' . $listItem->route . '/' . $object->slug . $suffix);
    }

    public function test_an_unknown_path_renders_the_error_page(): void
    {
        $response = $this->get('/' . $this->locale() . '/zz-no-such-page');

        $response->assertOk();

        $this->assertSame('error.show.404', Route::current()?->getName());
    }

    public function test_the_search_page_renders(): void
    {
        if (!Route::has('special.search.form')) {
            $this->markTestSkipped('No search route registered.');
        }

        $this->assertRenders('/' . $this->locale() . '/search', 'special.search');
    }
}
