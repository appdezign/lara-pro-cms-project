<?php

namespace Tests\Feature;

use Lara\Common\Models\Tag;
use Lara\Common\Routes\FrontRouteContext;
use Lara\Front\Http\Lara\FrontActiveRoute;
use Lara\Front\Services\FrontEntityResolver;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Tests\TestCase;

/**
 * The tag menu of a list links each tag through the tag route pattern of its
 * route: from the route context on menu routes, contenttag.{resource}.{tag}.index
 * on the content lists. Without a pattern, or for a tag whose route is not
 * registered yet, the tag is shown without a link instead of failing on route().
 */
class TagLinkRenderTest extends TestCase
{
    protected function setUp(): void
    {
        putenv(LaravelLocalization::ENV_ROUTE_KEY.'=nl');

        parent::setUp();
    }

    protected function tearDown(): void
    {
        putenv(LaravelLocalization::ENV_ROUTE_KEY.'=');

        parent::tearDown();
    }

    public function test_a_tag_is_linked_through_the_tag_route_of_the_menu_item(): void
    {
        $tag = $this->blogTag();
        $activeroute = new FrontActiveRoute;
        $activeroute->setTagRoutePattern(FrontRouteContext::forRouteName('entitytag.blogs.news.index')?->tagRoutePattern);

        $html = $this->renderTagMenu($tag, $activeroute);

        $this->assertStringContainsString('href="'.route($activeroute->getTagRoute($tag->route)).'"', $html);
    }

    /**
     * The content lists (contenttag.*) have no menu item and no route context, but they do have
     * a registered route per tag: contenttag.{resource}.{tag}.index.
     */
    public function test_on_a_content_list_a_tag_links_to_its_content_tag_route(): void
    {
        $tag = $this->blogTag();
        $activeroute = app(FrontEntityResolver::class)->getLaraActiveRoute('contenttag.blogs.index');

        $html = $this->renderTagMenu($tag, $activeroute);

        $this->assertStringContainsString('href="'.route('contenttag.blogs.'.$tag->route.'.index').'"', $html);
    }

    public function test_without_a_tag_route_a_tag_is_shown_without_a_link(): void
    {
        $tag = $this->blogTag();

        $html = $this->renderTagMenu($tag, new FrontActiveRoute);

        $this->assertStringContainsString($tag->title, $html);
        $this->assertStringNotContainsString('href=', $html);
    }

    public function test_a_tag_whose_route_is_not_registered_yet_is_shown_without_a_link(): void
    {
        $tag = $this->blogTag();
        $tag->route = 'zz-tag-after-the-route-cache';
        $activeroute = new FrontActiveRoute;
        $activeroute->setTagRoutePattern(FrontRouteContext::forRouteName('entitytag.blogs.news.index')?->tagRoutePattern);

        $html = $this->renderTagMenu($tag, $activeroute);

        $this->assertStringContainsString($tag->title, $html);
        $this->assertStringNotContainsString('href=', $html);
    }

    private function renderTagMenu(Tag $node, FrontActiveRoute $activeroute): string
    {
        $data = (object) ['params' => new class
        {
            public function getFilterByTaxonomy(): ?string
            {
                return null;
            }
        }];

        return view('content._partials.tag_menu_render', ['node' => $node, 'activeroute' => $activeroute, 'data' => $data])->render();
    }

    private function blogTag(): Tag
    {
        $tag = Tag::resourceIs('blogs')->langIs('nl')->whereNotNull('route')->orderBy('id')->first();

        if (! $tag) {
            $this->markTestSkipped('No blogs tag with a route.');
        }

        return $tag;
    }
}
