<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Lara\Admin\Enums\MenuItemType;
use Lara\App\Sitemap\GoogleSitemapGenerator;
use Lara\Common\Models\Entity;
use Lara\Common\Models\Menu;
use Lara\Common\Models\MenuItem;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Tests\TestCase;

/**
 * The Google sitemap (laracms/app/Sitemap/GoogleSitemapGenerator, run daily by the scheduler):
 * every main-menu item, plus the detail pages of each module the front shows.
 *
 * MenuItem::type is cast to MenuItemType; the generator once compared it with strings, which
 * were always false, so no module detail page was ever listed and the module branch (still on
 * the v8 entity API) never ran. These tests run that branch against the test database's
 * "news" module (blogs: terms, status and expiration).
 *
 * The sitemap is written to a temporary public path, never the project's public/sitemap.xml. The
 * tests write to the database inside a transaction (DatabaseTransactions), so the shared test
 * database is left as it was.
 */
class GoogleSitemapGeneratorTest extends TestCase
{
    use DatabaseTransactions;

    private string $publicPath;

    private string|false $routeLocale;

    protected function setUp(): void
    {
        // the routing locale, so the listed URLs can be requested (see FrontendRenderTest). Set
        // before the application is created: refreshApplication() after parent::setUp() would
        // replace the database connection that DatabaseTransactions wraps, and every write of
        // these tests would be committed to the shared test database.
        $this->routeLocale = getenv(LaravelLocalization::ENV_ROUTE_KEY);
        putenv(LaravelLocalization::ENV_ROUTE_KEY.'=nl');

        parent::setUp();

        $this->assertGreaterThan(0, DB::transactionLevel(), 'These tests write: they must run inside a transaction.');
        $this->assertSame('nl', config('app.locale'), 'The routing locale set above must be the default locale.');

        $this->publicPath = sys_get_temp_dir().'/lara-sitemap-'.uniqid();
        File::ensureDirectoryExists($this->publicPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicPath);

        parent::tearDown();

        putenv($this->routeLocale === false ? LaravelLocalization::ENV_ROUTE_KEY : LaravelLocalization::ENV_ROUTE_KEY.'='.$this->routeLocale);
    }

    public function test_menu_pages_and_the_published_objects_of_a_module_are_listed(): void
    {
        [$item, $entity] = $this->moduleItem();
        $object = $this->visibleObject($entity);
        $suffix = $entity->objrel_has_terms ? '.html' : '';

        $urls = array_keys($this->generate());

        $this->assertContains(url('/').'/'.$this->locale().'/'.$item->route, $urls, 'The module page itself.');
        $this->assertContains(url('/').'/'.$this->locale().'/'.$item->route.'/'.$object->slug.$suffix, $urls, 'A published object of the module, on its detail route.');

        foreach ($this->mainMenuItems() as $menuItem) {
            $this->assertContains(url('/').'/'.$this->locale().'/'.$menuItem->route, $urls, "Main-menu item {$menuItem->id}.");
        }
    }

    public function test_unpublished_expired_and_other_language_objects_are_not_listed(): void
    {
        [$item, $entity] = $this->moduleItem();
        $object = $this->visibleObject($entity);
        $table = (new $entity->model_class)->getTable();
        $detail = url('/').'/'.$this->locale().'/'.$item->route.'/'.$object->slug.($entity->objrel_has_terms ? '.html' : '');

        DB::table($table)->where('id', $object->id)->update(['publish' => 0]);
        $this->assertArrayNotHasKey($detail, $this->generate(), 'Unpublished.');

        DB::table($table)->where('id', $object->id)->update(['publish' => 1, 'publish_to' => now()->subDay()]);
        $this->assertArrayNotHasKey($detail, $this->generate(), 'Expired.');

        DB::table($table)->where('id', $object->id)->update(['publish_to' => null, 'language' => 'zz']);
        $this->assertArrayNotHasKey($detail, $this->generate(), 'Another language.');
    }

    public function test_lastmod_comes_from_the_page_and_from_the_newest_object(): void
    {
        [$item, $entity] = $this->moduleItem();
        $object = $this->visibleObject($entity);
        $home = $this->mainMenuItems()->firstWhere('is_home', 1) ?? $this->fail('No home page in the main menu.');

        DB::table('lara_content_pages')->where('id', $home->object_id)->update(['updated_at' => '2021-03-04 10:00:00']);
        DB::table((new $entity->model_class)->getTable())->where('id', $object->id)->update(['updated_at' => '2022-05-06 10:00:00']);
        DB::table('lara_menu_menu_items')->whereIn('id', [$home->id, $item->id])->update(['updated_at' => '2020-01-01 10:00:00']);

        $sitemap = $this->generate();

        $this->assertSame('2021-03-04', $sitemap[url('/').'/'.$this->locale().'/'.$home->route], 'A page item: the page, not the menu item.');
        $this->assertSame('2022-05-06', $sitemap[url('/').'/'.$this->locale().'/'.$item->route], 'A module item: its newest visible object.');
    }

    public function test_non_ascii_routes_are_not_encoded_twice(): void
    {
        [$item] = $this->moduleItem();
        DB::table('lara_menu_menu_items')->where('id', $item->id)->update(['route' => 'coördinatie']);

        $xml = $this->generateXml();

        $this->assertNotFalse(simplexml_load_string($xml), 'Valid XML.');
        $this->assertStringContainsString('/'.$this->locale().'/coördinatie<', $xml, 'UTF-8 as stored (utf8_encode() made it "coÃ¶rdinatie").');
    }

    public function test_every_listed_url_renders(): void
    {
        $this->moduleItem();

        foreach (array_keys($this->generate()) as $url) {
            $response = $this->get(parse_url($url, PHP_URL_PATH));
            $matched = Route::current()?->getName() ?? '(none)';

            $this->assertSame(200, $response->status(), "{$url} (matched route: {$matched}).");
            $this->assertNotSame('error.show.404', $matched, "{$url} rendered the error page.");
        }
    }

    /**
     * The sitemap as loc => lastmod.
     *
     * @return array<string, string>
     */
    private function generate(): array
    {
        preg_match_all('#<loc>([^<]+)</loc>\s*<lastmod>([^<]*)</lastmod>#', $this->generateXml(), $matches, PREG_SET_ORDER);

        $urls = [];
        foreach ($matches as [, $loc, $lastmod]) {
            $urls[htmlspecialchars_decode($loc)] = $lastmod;
        }

        return $urls;
    }

    /**
     * The generator writes public/sitemap.xml: the public path points to a temporary folder only
     * while it runs (the pages need the real one, with the Vite manifests).
     */
    private function generateXml(): string
    {
        $publicPath = public_path();
        $this->app->usePublicPath($this->publicPath);

        try {
            (new GoogleSitemapGenerator)([$this->locale()]);
        } finally {
            $this->app->usePublicPath($publicPath);
        }

        return File::get($this->publicPath.'/sitemap.xml');
    }

    private function locale(): string
    {
        return config('app.locale');
    }

    /**
     * @return Collection<int, MenuItem>
     */
    private function mainMenuItems()
    {
        $menu = Menu::where('slug', 'main')->first() ?? $this->fail('No main menu in the test database.');

        return MenuItem::where('menu_id', $menu->id)->where('language', $this->locale())->whereNull('tag_id')->get();
    }

    /**
     * A main-menu module item whose entity has a show view and a usable model.
     *
     * @return array{MenuItem, Entity}
     */
    private function moduleItem(): array
    {
        foreach ($this->mainMenuItems()->where('type', MenuItemType::Entity) as $item) {
            $entity = Entity::find($item->entity_id);

            if ($entity !== null && class_exists((string) $entity->model_class) && $entity->views()->where('method', 'show')->exists()) {
                return [$item, $entity];
            }
        }

        $this->markTestSkipped('No main-menu module item with a show view in the test database.');
    }

    private function visibleObject(Entity $entity): object
    {
        $query = $entity->model_class::langIs($this->locale());

        if ($entity->col_has_status) {
            $query->isPublished();

            if ($entity->col_has_expiration) {
                $query->isNotExpired();
            }
        }

        return $query->whereNotNull('slug')->first() ?? $this->markTestSkipped("No visible {$entity->resource_slug} object in the test database.");
    }
}
