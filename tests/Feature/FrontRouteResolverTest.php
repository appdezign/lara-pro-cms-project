<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Lara\App\Models\Blog;
use Lara\App\Models\Event;
use Lara\Common\Models\MenuItem;
use Lara\Front\Services\FrontEntityResolver;
use Lara\Front\Services\FrontRouteResolver;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * How the front resolves routes and visibility. The SEO route of an entity list
 * is the route of its menu item. Menu routes are
 * registered per locale and named after their URL, so the menu item has to be
 * the one of the current locale; another locale's route does not exist in the
 * request.
 */
class FrontRouteResolverTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return array<string, array{string}>
     */
    public static function locales(): array
    {
        return [
            'dutch' => ['nl'],
            'english' => ['en'],
        ];
    }

    /**
     * Decided 2026-10-11: like the lists, expiry only counts for an entity with expiration
     * enabled. Events have a status but no expiration; blogs have both.
     */
    public function test_a_passed_expiry_only_hides_an_object_of_an_entity_with_expiration(): void
    {
        $events = $this->entity('events');
        $blogs = $this->entity('blogs');
        $published = ['publish' => 1, 'publish_from' => now()->subWeek(), 'publish_to' => now()->subDay()];

        $eventIsHidden = $this->resolver()->isHiddenObject($events, Event::factory()->make($published));
        $blogIsHidden = $this->resolver()->isHiddenObject($blogs, Blog::factory()->make($published));

        $this->assertFalse($eventIsHidden);
        $this->assertTrue($blogIsHidden);
    }

    #[DataProvider('locales')]
    public function test_the_seo_route_is_the_menu_route_of_the_current_locale(string $locale): void
    {
        $expected = MenuItem::langIs($locale)
            ->whereNull('tag_id')
            ->whereHas('entity', fn ($query) => $query->where('resource_slug', 'blogs'))
            ->value('routename');

        if (! $expected) {
            $this->markTestSkipped('No blogs menu item in '.$locale.'.');
        }

        LaravelLocalization::setLocale($locale);

        $route = app(FrontRouteResolver::class)->getFrontSeoRoute('blogs', 'index');

        $this->assertSame($expected, $route);
    }

    private function resolver(): FrontRouteResolver
    {
        return app(FrontRouteResolver::class);
    }

    private function entity(string $resourceSlug): object
    {
        $entity = app(FrontEntityResolver::class)->getResourceBySlug($resourceSlug);

        if (! $entity) {
            $this->markTestSkipped('No '.$resourceSlug.' entity in the current database.');
        }

        return $entity;
    }
}
