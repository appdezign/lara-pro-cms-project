<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\RedirectResponse;
use Lara\App\Models\Location;
use Lara\Front\Http\Lara\FrontActiveRoute;
use Lara\Front\Services\FrontEntityResolver;
use Lara\Front\Services\FrontObjectRepository;
use Tests\TestCase;

/**
 * Finding the object of a detail page by its slug. Locations have no media, so
 * the lookup starts without eager loading: the case where its conditions used
 * to be lost and the first record of the table came back for any slug.
 */
class FrontObjectRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_an_unknown_slug_finds_no_object(): void
    {
        Location::factory()->create(['language' => 'nl']);

        $result = $this->repository()->getSingleFrontObject('nl', $this->locations(), 'zz-no-such-location');

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function test_a_slug_finds_its_own_object(): void
    {
        Location::factory()->create(['language' => 'nl']);
        $location = Location::factory()->create(['language' => 'nl']);

        $result = $this->repository()->getSingleFrontObject('nl', $this->locations(), $location->slug);

        $this->assertTrue($location->is($result));
    }

    public function test_a_slug_only_finds_an_object_in_the_requested_language(): void
    {
        $location = Location::factory()->create(['language' => 'en']);

        $result = $this->repository()->getSingleFrontObject('nl', $this->locations(), $location->slug);

        $this->assertInstanceOf(RedirectResponse::class, $result);
    }

    public function test_without_a_menu_item_the_list_url_is_the_entity_content_list(): void
    {
        $location = Location::factory()->create(['language' => 'nl']);
        $activeroute = new FrontActiveRoute;
        $activeroute->setPrefix('content');

        $url = $this->repository()->getEntityListUrl('nl', $this->locations(), $activeroute, $location, null, false);

        $this->assertSame(url('content/locations'), $url);
    }

    public function test_the_list_url_comes_from_the_menu_route_of_the_request(): void
    {
        $location = Location::factory()->create(['language' => 'nl']);
        $activeroute = new FrontActiveRoute;
        $activeroute->setPrefix('content');
        $activeroute->setMenuRoute('special.search.form');

        $url = $this->repository()->getEntityListUrl('nl', $this->locations(), $activeroute, $location, null, false);

        $this->assertSame(url('search'), $url);
    }

    private function repository(): FrontObjectRepository
    {
        return app(FrontObjectRepository::class);
    }

    private function locations(): object
    {
        $entity = app(FrontEntityResolver::class)->getResourceBySlug('locations');

        if (! $entity || $entity->hasImages() || $entity->hasVideos() || $entity->hasVideoFiles() || $entity->hasFiles()) {
            $this->markTestSkipped('No locations entity without media in the current database.');
        }

        return $entity;
    }
}
