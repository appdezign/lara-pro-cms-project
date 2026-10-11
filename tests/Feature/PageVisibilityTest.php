<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Lara\Common\Models\Entity;
use Lara\Common\Models\MenuItem;
use Lara\Common\Models\Page;
use Lara\Common\Models\User;
use Lara\Common\Routes\MenuRouteName;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Tests\TestCase;

/**
 * A page that is not visible (unpublished, or publish_from in the future) is
 * not shown through its menu route. Pages have no list, so the visitor gets the
 * error page.
 *
 * The test database has no page in its menu besides the home page, and front
 * routes are built from the menu when the application boots. Each test adds a
 * page with a menu item and rebuilds the application, so it cannot use
 * DatabaseTransactions: it deletes what it added in tearDown() (see
 * FrontFormTest).
 */
class PageVisibilityTest extends TestCase
{
    private const PAGE_ROUTE = 'zz-page-test';

    /**
     * @var array<string, int>
     */
    private array $maxIds = [];

    private int $pageId;

    protected function setUp(): void
    {
        putenv(LaravelLocalization::ENV_ROUTE_KEY.'=nl');

        parent::setUp();

        foreach (['lara_menu_menu_items', 'lara_content_pages', 'lara_object_seo'] as $table) {
            $this->maxIds[$table] = (int) DB::table($table)->max('id');
        }

        $this->pageId = $this->makePageWithMenuItem();

        $this->refreshApplication();
    }

    protected function tearDown(): void
    {
        foreach ($this->maxIds as $table => $maxId) {
            DB::table($table)->where('id', '>', $maxId)->delete();
        }

        putenv(LaravelLocalization::ENV_ROUTE_KEY.'=');

        parent::tearDown();
    }

    public function test_a_published_page_is_shown(): void
    {
        $response = $this->get('/nl/'.self::PAGE_ROUTE);

        $response->assertOk()->assertSee('Zz page test');
    }

    public function test_an_unpublished_page_redirects_to_the_error_page(): void
    {
        Page::whereKey($this->pageId)->update(['publish' => 0]);

        $response = $this->get('/nl/'.self::PAGE_ROUTE);

        $response->assertRedirect(route('error.show.404', '404'));
    }

    public function test_a_page_published_in_the_future_redirects_to_the_error_page(): void
    {
        Page::whereKey($this->pageId)->update(['publish_from' => now()->addDay()]);

        $response = $this->get('/nl/'.self::PAGE_ROUTE);

        $response->assertRedirect(route('error.show.404', '404'));
    }

    public function test_a_logged_in_user_can_preview_an_unpublished_page_that_is_in_the_menu(): void
    {
        Page::whereKey($this->pageId)->update(['publish' => 0]);
        $this->actingAs(User::where('name', 'admin')->firstOrFail());

        $response = $this->get(route('content.pages.show', $this->pageId));

        $response->assertOk()->assertSee('Zz page test');
    }

    public function test_the_preview_of_a_visible_page_redirects_to_its_menu_url(): void
    {
        $this->actingAs(User::where('name', 'admin')->firstOrFail());

        $response = $this->get(route('content.pages.show', $this->pageId));

        $response->assertRedirect(url('/nl/'.self::PAGE_ROUTE));
    }

    private function makePageWithMenuItem(): int
    {
        $entity = Entity::where('resource_slug', 'pages')->first();
        $menuId = MenuItem::langIs('nl')->value('menu_id');

        if (! $entity || ! $menuId) {
            $this->markTestSkipped('No pages entity or Dutch menu in the current database.');
        }

        $page = Page::factory()->create([
            'title' => 'Zz page test',
            'language' => 'nl',
            'publish' => 1,
            'publish_from' => now()->subDay(),
        ]);

        $menuItem = MenuItem::create([
            'language' => 'nl',
            'menu_id' => $menuId,
            'title' => 'zz page test',
            'type' => 'page',
            'route' => self::PAGE_ROUTE,
            'entity_id' => $entity->id,
            'entity_view_id' => $entity->views()->where('method', 'show')->value('id'),
            'object_id' => $page->id,
            'publish' => 1,
        ]);

        $menuItem->routename = MenuRouteName::forMenuItem($menuItem, $menuItem->entity, $menuItem->entityview);
        $menuItem->save();

        return $page->id;
    }
}
