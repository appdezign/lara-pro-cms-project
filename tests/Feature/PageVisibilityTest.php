<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Lara\Common\Models\Entity;
use Lara\Common\Models\Menu;
use Lara\Common\Models\MenuItem;
use Lara\Common\Models\Page;
use Lara\Common\Models\User;
use Lara\Common\Routes\MenuRouteName;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Tests\TestCase;

/**
 * A page with a menu route is always shown, whatever the status of its menu
 * item or of the page itself: an unpublished menu item only leaves the frontend
 * menu (decided 2026-10-11). A preview (content.pages.show) redirects to the
 * menu URL in the page's own language.
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

    public function test_an_unpublished_page_with_a_published_menu_item_is_shown(): void
    {
        Page::whereKey($this->pageId)->update(['publish' => 0]);

        $response = $this->get('/nl/'.self::PAGE_ROUTE);

        $response->assertOk()->assertSee('Zz page test');
    }

    public function test_a_scheduled_page_with_a_published_menu_item_is_shown(): void
    {
        Page::whereKey($this->pageId)->update(['publish_from' => now()->addDay()]);

        $response = $this->get('/nl/'.self::PAGE_ROUTE);

        $response->assertOk()->assertSee('Zz page test');
    }

    public function test_a_page_whose_menu_item_is_unpublished_is_still_shown(): void
    {
        $this->unpublishMenuItem();

        $response = $this->get('/nl/'.self::PAGE_ROUTE);

        $response->assertOk()->assertSee('Zz page test');
    }

    public function test_a_page_under_an_unpublished_parent_menu_item_is_still_shown(): void
    {
        $menuItem = MenuItem::where('route', self::PAGE_ROUTE)->firstOrFail();
        $parent = MenuItem::create([
            'language' => 'nl',
            'menu_id' => $menuItem->menu_id,
            'title' => 'zz hidden section',
            'type' => 'parent',
            'publish' => 0,
        ]);
        $menuItem->appendToNode($parent)->save();

        $response = $this->get('/nl/'.self::PAGE_ROUTE);

        $response->assertOk()->assertSee('Zz page test');
    }

    public function test_the_preview_of_a_page_whose_menu_item_is_unpublished_redirects_to_its_menu_url(): void
    {
        $this->unpublishMenuItem();

        $response = $this->get(route('content.pages.show', $this->pageId));

        $response->assertRedirect(url('/nl/'.self::PAGE_ROUTE));
    }

    public function test_the_preview_of_a_page_in_another_language_redirects_to_its_menu_url_in_that_language(): void
    {
        $englishPage = Page::factory()->create(['title' => 'Zz english page', 'language' => 'en']);
        $dutchMenuItem = MenuItem::where('route', self::PAGE_ROUTE)->firstOrFail();
        MenuItem::create([
            ...$dutchMenuItem->only(['menu_id', 'type', 'entity_id', 'entity_view_id', 'publish']),
            'language' => 'en',
            'title' => 'zz english page',
            'route' => 'zz-english-page',
            'object_id' => $englishPage->id,
        ]);

        $response = $this->get(route('content.pages.show', $englishPage->id));

        $response->assertRedirect(url('/en/zz-english-page'));
    }

    public function test_the_preview_of_a_page_with_a_published_menu_item_redirects_to_its_menu_url(): void
    {
        $this->actingAs(User::where('name', 'admin')->firstOrFail());

        $response = $this->get(route('content.pages.show', $this->pageId));

        $response->assertRedirect(url('/nl/'.self::PAGE_ROUTE));
    }

    public function test_the_preview_of_an_unpublished_page_with_a_published_menu_item_redirects_to_its_menu_url(): void
    {
        Page::whereKey($this->pageId)->update(['publish' => 0]);
        $this->actingAs(User::where('name', 'admin')->firstOrFail());

        $response = $this->get(route('content.pages.show', $this->pageId));

        $response->assertRedirect(url('/nl/'.self::PAGE_ROUTE));
    }

    /**
     * A page linked from more than one menu: the preview goes to the item of the first menu
     * (lowest menu id, the main menu in a standard site), whatever order the rows were made in.
     */
    public function test_the_preview_of_a_page_in_two_menus_redirects_to_the_first_menu(): void
    {
        $mainMenuItem = MenuItem::where('route', self::PAGE_ROUTE)->firstOrFail();
        $otherMenuId = Menu::whereKeyNot($mainMenuItem->menu_id)->orderBy('id')->value('id');

        if (! $otherMenuId || $otherMenuId < $mainMenuItem->menu_id) {
            $this->markTestSkipped('No second menu after the main menu.');
        }

        // recreate the main-menu item after the other one, so it is not the first row
        $attributes = $mainMenuItem->only(['language', 'menu_id', 'title', 'type', 'route', 'routename', 'entity_id', 'entity_view_id', 'object_id', 'publish']);
        DB::table('lara_menu_menu_items')->where('id', $mainMenuItem->id)->delete();
        MenuItem::create([...$attributes, 'menu_id' => $otherMenuId, 'title' => 'zz page top', 'route' => 'zz-page-top']);
        MenuItem::create($attributes);

        $response = $this->get(route('content.pages.show', $this->pageId));

        $response->assertRedirect(url('/nl/'.self::PAGE_ROUTE));
    }

    public function test_the_preview_skips_a_menu_item_without_a_route(): void
    {
        $mainMenuItem = MenuItem::where('route', self::PAGE_ROUTE)->firstOrFail();
        $otherMenuId = Menu::whereKeyNot($mainMenuItem->menu_id)->orderBy('id')->value('id');

        if (! $otherMenuId || $otherMenuId < $mainMenuItem->menu_id) {
            $this->markTestSkipped('No second menu after the main menu.');
        }

        MenuItem::create([
            ...$mainMenuItem->only(['language', 'type', 'entity_id', 'entity_view_id', 'object_id', 'publish']),
            'menu_id' => $otherMenuId,
            'title' => 'zz page top',
            'route' => 'zz-page-top',
        ]);
        $mainMenuItem->update(['route' => '']);

        $response = $this->get(route('content.pages.show', $this->pageId));

        $response->assertRedirect(url('/nl/zz-page-top'));
    }

    /**
     * The route of the menu item stays registered (routes are built for every
     * page menu item); only its publish flag changes.
     */
    private function unpublishMenuItem(): void
    {
        MenuItem::where('route', self::PAGE_ROUTE)->update(['publish' => 0]);
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
