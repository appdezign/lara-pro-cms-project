<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Lara\App\Models\Blog;
use Lara\Common\Models\EntityView;
use Lara\Common\Models\MenuItem;
use Lara\Common\Models\Page;
use Lara\Common\Models\User;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Tests\TestCase;

/**
 * Which content the public site shows. A record is visible when publish = 1
 * and publish_from is empty or in the past, and only on the site of its own
 * language. Uses the blog list from the menu; see FrontendRenderTest for why
 * the routing locale is forced and why the matched route is checked.
 */
class PublishVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * The routing locale is set before the application boots. Refreshing the
     * application afterwards (as FrontendRenderTest does) would open a new
     * database connection outside the DatabaseTransactions transaction, and
     * the records these tests create would be committed.
     */
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

    public function test_a_published_blog_is_listed(): void
    {
        $this->blog('Visible blog', ['publish' => 1, 'publish_from' => now()->subDay()]);

        $this->get($this->blogListUrl())->assertSee('Visible blog');
    }

    public function test_an_unpublished_blog_is_not_listed(): void
    {
        $this->blog('Draft blog', ['publish' => 0]);

        $this->get($this->blogListUrl())->assertDontSee('Draft blog');
    }

    public function test_a_blog_published_in_the_future_is_not_listed(): void
    {
        $this->blog('Future blog', ['publish' => 1, 'publish_from' => now()->addDay()]);

        $this->get($this->blogListUrl())->assertDontSee('Future blog');
    }

    public function test_an_english_blog_is_not_listed_on_the_dutch_site(): void
    {
        $this->blog('English blog', ['language' => 'en', 'publish' => 1, 'publish_from' => now()->subDay()]);

        $this->get($this->blogListUrl())->assertDontSee('English blog');
    }

    public function test_the_detail_page_of_a_published_blog_renders(): void
    {
        $blog = $this->blog('Visible blog', ['publish' => 1, 'publish_from' => now()->subDay()]);

        $this->get($this->blogDetailUrl($blog))->assertSee('Visible blog');

        $this->assertNotSame('error.show.404', Route::current()?->getName());
    }

    public function test_the_detail_page_of_an_unpublished_blog_redirects_to_the_list(): void
    {
        $blog = $this->blog('Draft blog', ['publish' => 0]);

        $response = $this->get($this->blogDetailUrl($blog));

        $response->assertRedirect(url('/nl/'.$this->blogMenuItem()->route));
    }

    public function test_the_detail_page_of_a_blog_published_in_the_future_redirects_to_the_list(): void
    {
        $blog = $this->blog('Future blog', ['publish' => 1, 'publish_from' => now()->addDay()]);

        $response = $this->get($this->blogDetailUrl($blog));

        $response->assertRedirect(url('/nl/'.$this->blogMenuItem()->route));
    }

    public function test_the_detail_page_of_an_expired_blog_redirects_to_the_list(): void
    {
        $blog = $this->blog('Expired blog', ['publish' => 1, 'publish_from' => now()->subWeek(), 'publish_expire' => 1, 'publish_to' => now()->subDay()]);

        $response = $this->get($this->blogDetailUrl($blog));

        $response->assertRedirect(url('/nl/'.$this->blogMenuItem()->route));
    }

    public function test_a_blog_expiring_this_second_is_hidden_on_the_list_and_the_detail_page(): void
    {
        $this->freezeSecond();
        $blog = $this->blog('Expiring blog', ['publish' => 1, 'publish_from' => now()->subWeek(), 'publish_expire' => 1, 'publish_to' => now()]);

        $this->get($this->blogListUrl())->assertDontSee('Expiring blog');

        $this->get($this->blogDetailUrl($blog))->assertRedirect(url('/nl/'.$this->blogMenuItem()->route));
    }

    /**
     * Decided 2026-10-11: the home page is the entry of the site and always renders, also when
     * it is unpublished or scheduled (BaseHomeController has no visibility check).
     */
    public function test_an_unpublished_home_page_still_renders(): void
    {
        $homePageId = MenuItem::langIs('nl')->where('is_home', 1)->value('object_id');

        if (! $homePageId) {
            $this->markTestSkipped('No Dutch home page in the menu.');
        }

        Page::whereKey($homePageId)->update(['publish' => 0, 'publish_from' => now()->addDay()]);

        $this->get('/nl')->assertOk();

        $this->assertSame('special.home.show', Route::current()?->getName());
    }

    public function test_an_unknown_slug_redirects_to_the_error_page(): void
    {
        $item = $this->blogMenuItem();
        $suffix = $item->entity->objrel_has_terms ? '.html' : '';

        $response = $this->get('/nl/'.$item->route.'/zz-no-such-blog'.$suffix);

        $response->assertRedirect(route('error.show.404', '404'));
    }

    public function test_an_unknown_page_id_redirects_to_the_error_page(): void
    {
        $response = $this->get(route('content.pages.show', 999999));

        $response->assertRedirect(route('error.show.404', '404'));
    }

    public function test_a_logged_in_user_can_preview_a_scheduled_blog(): void
    {
        $blog = $this->blog('Scheduled blog', ['publish' => 1, 'publish_from' => now()->addDay()]);
        $this->actingAs(User::where('name', 'admin')->firstOrFail());

        $response = $this->get($this->previewUrl($blog));

        $response->assertOk()->assertSee('Scheduled blog');
    }

    public function test_a_guest_cannot_preview_a_scheduled_blog(): void
    {
        $blog = $this->blog('Scheduled blog', ['publish' => 1, 'publish_from' => now()->addDay()]);

        $response = $this->get($this->previewUrl($blog));

        $response->assertRedirect(route('error.show.404', '404'));
    }

    public function test_the_preview_of_a_visible_blog_redirects_to_its_public_url(): void
    {
        $blog = $this->blog('Visible blog', ['publish' => 1, 'publish_from' => now()->subDay()]);
        $this->actingAs(User::where('name', 'admin')->firstOrFail());

        $response = $this->get($this->previewUrl($blog));

        $response->assertRedirect(url($this->blogDetailUrl($blog)));
    }

    public function test_a_logged_in_user_can_preview_an_unpublished_blog(): void
    {
        $blog = $this->blog('Draft blog', ['publish' => 0]);
        $this->actingAs(User::where('name', 'admin')->firstOrFail());

        $response = $this->get($this->previewUrl($blog));

        $response->assertOk()->assertSee('Draft blog');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function blog(string $title, array $attributes): Blog
    {
        return Blog::factory()->create(['title' => $title, 'language' => 'nl', ...$attributes]);
    }

    /**
     * The preview route the admin links to: the content route of the object, by id.
     */
    private function previewUrl(Blog $blog): string
    {
        $prefix = $this->blogMenuItem()->entity->objrel_has_terms ? 'contenttag' : 'content';

        return route($prefix.'.blogs.index.show', $blog->id);
    }

    private function blogMenuItem(): MenuItem
    {
        $item = MenuItem::langIs('nl')
            ->whereNotNull('route')
            ->whereNull('tag_id')
            ->whereHas('entity', fn ($query) => $query->where('resource_slug', 'blogs'))
            ->get()
            ->first(fn (MenuItem $item): bool => Route::has($item->routename));

        if (! $item) {
            $this->markTestSkipped('No blog list in the Dutch menu.');
        }

        return $item;
    }

    /**
     * The blog list only shows blogs with an image (image_required on its
     * index view). Factory blogs have none, so the list tests switch that off.
     */
    private function blogListUrl(): string
    {
        EntityView::whereHas('entity', fn ($query) => $query->where('resource_slug', 'blogs'))
            ->where('method', 'index')
            ->each(fn (EntityView $view) => $view->update(['image_required' => 0]));

        return '/nl/'.$this->blogMenuItem()->route;
    }

    private function blogDetailUrl(Blog $blog): string
    {
        $item = $this->blogMenuItem();
        $suffix = $item->entity->objrel_has_terms ? '.html' : '';

        return '/nl/'.$item->route.'/'.$blog->slug.$suffix;
    }
}
