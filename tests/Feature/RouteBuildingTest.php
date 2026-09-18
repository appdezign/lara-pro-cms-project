<?php

namespace Tests\Feature;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Lara\Common\Models\Entity;
use Lara\Common\Models\MenuItem;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Tests\TestCase;

/**
 * Covers the frontend routing table, which is built at boot from the menu and
 * the entity rows rather than declared statically.
 *
 * Route registration happens once per application boot, for a single locale, so
 * a "per locale" assertion has to re-bootstrap the application with the locale
 * forced. That is the same mechanism lara:route:cache uses to build one cache
 * file per locale, and it is exercised here without touching the real cache.
 */
class RouteBuildingTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv(LaravelLocalization::ENV_ROUTE_KEY . '=');

        parent::tearDown();
    }

    /**
     * Boot a fresh application with the routing locale forced, and return its
     * registered route names.
     *
     * @return Collection<int, string>
     */
    private function routeNamesForLocale(string $locale): Collection
    {
        putenv(LaravelLocalization::ENV_ROUTE_KEY . '=' . $locale);

        $this->refreshApplication();

        return collect(Route::getRoutes()->getRoutes())
            ->map(fn($route) => $route->getName())
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function routeUrisForLocale(string $locale): Collection
    {
        putenv(LaravelLocalization::ENV_ROUTE_KEY . '=' . $locale);

        $this->refreshApplication();

        return collect(Route::getRoutes()->getRoutes())
            ->map(fn($route) => $route->uri())
            ->values();
    }

    /**
     * @return list<string>
     */
    private function supportedLocales(): array
    {
        return array_keys(config('laravellocalization.supportedLocales'));
    }

    public function test_more_than_one_locale_is_configured(): void
    {
        $this->assertGreaterThan(
            1,
            count($this->supportedLocales()),
            'These tests only mean something for a multi-language install.'
        );
    }

    public function test_every_locale_builds_a_route_table_without_error(): void
    {
        foreach ($this->supportedLocales() as $locale) {
            $names = $this->routeNamesForLocale($locale);

            $this->assertGreaterThan(
                0,
                $names->count(),
                'Locale "' . $locale . '" produced no routes at all.'
            );
        }
    }

    public function test_route_names_are_unique_within_a_locale(): void
    {
        foreach ($this->supportedLocales() as $locale) {
            $names = $this->routeNamesForLocale($locale);
            $duplicates = $names->duplicates()->values()->all();

            $this->assertSame(
                [],
                $duplicates,
                'Locale "' . $locale . '" registers duplicate route names, so the later '
                . 'one silently wins: ' . implode(', ', $duplicates)
            );
        }
    }

    public function test_frontend_routes_are_prefixed_with_the_locale(): void
    {
        foreach ($this->supportedLocales() as $locale) {
            $uris = $this->routeUrisForLocale($locale);

            // the /content/ fallback is registered for every entity, so it is
            // the reliable marker that the localised group was built
            $contentUris = $uris->filter(fn(string $uri) => str_contains($uri, 'content/'));

            $this->assertGreaterThan(
                0,
                $contentUris->count(),
                'Locale "' . $locale . '" has no /content/ fallback routes.'
            );

            foreach ($contentUris as $uri) {
                $this->assertStringStartsWith(
                    $locale . '/content/',
                    $uri,
                    'Fallback route "' . $uri . '" is not prefixed with locale "' . $locale . '".'
                );
            }
        }
    }

    public function test_every_entity_gets_a_content_fallback_route(): void
    {
        $locale = config('app.locale');
        $names = $this->routeNamesForLocale($locale);

        $entities = Entity::where('cgroup', 'entity')->pluck('resource_slug');

        $this->assertGreaterThan(0, $entities->count(), 'No entities to check.');

        foreach ($entities as $slug) {
            $hasIndex = $names->contains('content.' . $slug . '.index')
                || $names->contains('contenttag.' . $slug . '.index');

            $this->assertTrue(
                $hasIndex,
                'Entity "' . $slug . '" has no /content/ fallback index route.'
            );
        }
    }

    public function test_menu_items_produce_their_recorded_route_name(): void
    {
        $locale = config('app.locale');
        $names = $this->routeNamesForLocale($locale);

        $menuItems = MenuItem::langIs($locale)
            ->whereIn('type', ['page', 'entity', 'form'])
            ->whereNotNull('route')
            ->whereNotNull('routename')
            ->with('entity')
            ->get();

        $this->assertGreaterThan(0, $menuItems->count(), 'No routable menu items to check.');

        foreach ($menuItems as $item) {
            // a menu item whose entity row is gone is deliberately skipped
            if (!$item->entity) {
                continue;
            }

            $this->assertTrue(
                $names->contains($item->routename),
                'Menu item ' . $item->id . ' ("' . $item->title . '") records routename "'
                . $item->routename . '" but no such route is registered.'
            );
        }
    }

    /**
     * Entities with terms use a ".html" suffix on the single-object route so a
     * slug can be told apart from a category segment.
     */
    public function test_entities_with_terms_get_an_html_suffixed_single_route(): void
    {
        $locale = config('app.locale');

        putenv(LaravelLocalization::ENV_ROUTE_KEY . '=' . $locale);
        $this->refreshApplication();

        $routes = collect(Route::getRoutes()->getRoutes());

        $termEntities = Entity::where('cgroup', 'entity')
            ->where('objrel_has_terms', 1)
            ->pluck('resource_slug');

        if ($termEntities->isEmpty()) {
            $this->markTestSkipped('No entities use terms in the current database.');
        }

        foreach ($termEntities as $slug) {
            $single = $routes->first(
                fn($route) => $route->getName() === 'contenttag.' . $slug . '.index.show'
            );

            $this->assertNotNull($single, 'No single route for term entity "' . $slug . '".');
            $this->assertStringEndsWith('.html', $single->uri());
        }
    }

    /**
     * Regression test: an entity-type menu item with no entity or view used to
     * fatal while the route file was being evaluated, taking down every route
     * on the site rather than just the misconfigured one.
     *
     * A dangling entity_id is not reachable - the foreign key cascades on
     * delete - but both entity_id and entity_view_id are nullable, so a menu
     * item can legitimately exist without them.
     *
     * @param array<string, mixed> $attributes
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('incompleteMenuItemProvider')]
    public function test_an_incomplete_menu_item_does_not_break_routing(array $attributes, string $case): void
    {
        $locale = config('app.locale');

        $orphan = MenuItem::create([
            'language'  => $locale,
            'menu_id'   => MenuItem::langIs($locale)->value('menu_id'),
            'title'     => 'zz orphan fixture',
            'type'      => 'entity',
            'route'     => 'zz-orphan-fixture',
            'routename' => 'entity.zzorphans.9999.index',
            'publish'   => 1,
            ...$attributes,
        ]);

        try {
            $names = $this->routeNamesForLocale($locale);

            $this->assertGreaterThan(
                0,
                $names->count(),
                'A menu item that ' . $case . ' must not prevent the route table from building.'
            );

            $this->assertFalse(
                $names->contains('entity.zzorphans.9999.index'),
                'The incomplete menu item must be skipped, not registered.'
            );

            // and the rest of the site is still routable
            $this->assertTrue(
                $names->contains('special.home.show'),
                'The home route must survive an incomplete menu item.'
            );
        } finally {
            $orphan->delete();
        }
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function incompleteMenuItemProvider(): array
    {
        return [
            'no entity'      => [['entity_id' => null, 'entity_view_id' => null], 'has no entity'],
            'no entity view' => [['entity_view_id' => null], 'has no entity view'],
        ];
    }
}
