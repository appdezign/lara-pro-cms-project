<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Lara\Common\Models\Entity;
use Lara\Common\Models\Tag;
use Lara\Common\Routes\RouteTagIndex;
use Tests\TestCase;

/**
 * The front route files build one route per tag, for every term-enabled
 * entity. They used to run Tag::resourceIs($slug) inside those loops - one
 * query per entity, 16 round trips to fetch 60 rows on this install.
 *
 * RouteTagIndex reads them once and groups in memory. These tests pin both
 * halves: that it returns exactly what the per-slug query returned, and that
 * it only queries once.
 */
class RouteTagIndexTest extends TestCase
{
    private function index(): RouteTagIndex
    {
        return app(RouteTagIndex::class);
    }

    public function test_it_is_a_singleton(): void
    {
        $this->assertSame($this->index(), $this->index());
    }

    /**
     * The index must be equivalent to the query it replaced, for every
     * resource that actually produces tag routes.
     */
    public function test_it_matches_the_per_resource_query(): void
    {
        $slugs = Entity::where('objrel_has_terms', 1)->pluck('resource_slug');

        $this->assertGreaterThan(0, $slugs->count(), 'No term-enabled entities to check.');

        foreach ($slugs as $slug) {
            $expected = Tag::resourceIs($slug)->whereNotNull('route')->get()
                ->pluck('id')->sort()->values()->all();

            $actual = $this->index()->forResource($slug)
                ->pluck('id')->sort()->values()->all();

            $this->assertSame($expected, $actual, 'Tag set differs for resource "'.$slug.'".');
        }
    }

    public function test_it_queries_the_tags_table_only_once(): void
    {
        $this->index()->flush();

        $queries = 0;
        DB::listen(function ($q) use (&$queries) {
            if (str_contains($q->sql, 'lara_object_tags')) {
                $queries++;
            }
        });

        // several lookups, as the route files do
        foreach (Entity::where('objrel_has_terms', 1)->pluck('resource_slug') as $slug) {
            $this->index()->forResource($slug);
        }

        $this->assertSame(1, $queries, 'The tag table should be read once, not once per resource.');
    }

    public function test_an_unknown_or_empty_resource_returns_an_empty_collection(): void
    {
        $this->assertTrue($this->index()->forResource('zz-no-such-resource')->isEmpty());
        $this->assertTrue($this->index()->forResource(null)->isEmpty());
        $this->assertTrue($this->index()->forResource('')->isEmpty());
    }

    public function test_only_routable_tags_are_included(): void
    {
        $withoutRoute = Tag::whereNull('route')->count();

        if ($withoutRoute === 0) {
            $this->markTestSkipped('No tags without a route to check against.');
        }

        $indexed = Entity::where('objrel_has_terms', 1)->pluck('resource_slug')
            ->flatMap(fn (string $slug): array => $this->index()->forResource($slug)->all());

        foreach ($indexed as $tag) {
            $this->assertNotNull($tag->route, 'Tag '.$tag->id.' has no route and should not be indexed.');
        }
    }
}
