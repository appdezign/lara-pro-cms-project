<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Lara\App\Models\Blog;
use Lara\Common\Models\Tag;
use Lara\Front\Http\Lara\FrontActiveRoute;
use Lara\Front\Http\Lara\FrontParams;
use Lara\Front\Services\FrontEntityResolver;
use Lara\Front\Services\FrontListBuilder;
use Tests\TestCase;

/**
 * A list sorted by tag, opened through a menu item with a tag, shows the subtree
 * of that tag: also the objects that have only one of its child tags (as v8 did).
 */
class SortByTagListTest extends TestCase
{
    use DatabaseTransactions;

    public function test_a_menu_tag_list_includes_the_objects_of_its_child_tags(): void
    {
        $parent = $this->blogTag('team-news');
        $child = $this->blogTag('tech-team-news');
        $other = $this->blogTag('home-blog-widget');

        $onParent = $this->blogWithTag($parent);
        $onChild = $this->blogWithTag($child);
        $outside = $this->blogWithTag($other);

        $tree = $this->sortedList($parent);

        $parentNode = $tree->category->first();
        $childNode = $parentNode->children->firstWhere('id', $child->id);

        $this->assertTrue($parentNode->objects->contains($onParent));
        $this->assertTrue($childNode->objects->contains($onChild));
        $this->assertTrue($childNode->objects->first()->relationLoaded('terms'));
        $this->assertFalse($this->allObjects($parentNode)->contains($outside));
    }

    private function sortedList(Tag $menuTag): object
    {
        $entity = app(FrontEntityResolver::class)->getResourceBySlug('blogs');

        $view = $entity->getViews()->firstWhere('method', 'index');
        $view->showtags = '_sortbytaxonomy';
        $view->image_required = 0;

        $activeroute = new FrontActiveRoute;
        $activeroute->setMethod('index');

        return app(FrontListBuilder::class)->getFrontObjects(Request::create('/'), 'nl', $entity, $activeroute, $menuTag, new FrontParams);
    }

    private function allObjects(Tag $node): Collection
    {
        return $node->children->reduce(
            fn ($objects, $child) => $objects->merge($this->allObjects($child)),
            collect($node->objects),
        );
    }

    private function blogTag(string $slug): Tag
    {
        $tag = Tag::where('resource_slug', 'blogs')->where('language', 'nl')->where('slug', $slug)->first();

        if (! $tag) {
            $this->markTestSkipped("No blog tag '{$slug}' in the test database.");
        }

        return $tag;
    }

    private function blogWithTag(Tag $tag): Blog
    {
        $blog = Blog::factory()->create(['language' => 'nl', 'publish' => 1, 'publish_from' => now()->subDay()]);
        $blog->terms()->attach($tag->id);

        return $blog;
    }
}
