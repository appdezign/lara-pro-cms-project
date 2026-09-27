<?php

namespace Tests\Feature;

use Illuminate\Routing\CompiledRouteCollection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Lara\App\Models\Blog;
use Lara\Common\Models\Entity;
use Lara\Common\Models\EntityView;
use Lara\Common\Models\MenuItem;
use Lara\Common\Models\Tag;
use Lara\Common\Routes\FrontRouteContext;
use Lara\Common\Routes\MenuRouteName;
use Lara\Front\Services\FrontEntityResolver;
use Lara\Front\Services\FrontMenuRepository;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Tests\TestCase;

/**
 * Menu routes carry their menu item in the route action (FrontRouteContext) instead of in
 * their name, and are named after their URL (MenuRouteName).
 *
 * The menu item ID used to be part of every menu route name, because the controllers read the
 * menu item from the name. It was needed so the same entity could be in the menu twice:
 * both routes must know their own menu item, for the "back" link on a detail page and for the
 * active menu item.
 *
 * The routes are built from the database when the application boots, so the tests that add
 * menu items rebuild the application, and clean up after themselves instead of relying on a
 * transaction (a rebuilt application does not see uncommitted rows).
 */
class MenuRouteContextTest extends TestCase
{
    /** The news item in the test database: the blogs entity, tagless, at "news". */
    private const NEWS_ROUTE = 'entitytag.blogs.news.index';

    protected function tearDown(): void
    {
        putenv(LaravelLocalization::ENV_ROUTE_KEY.'=');

        parent::tearDown();
    }

    private function rebuildRoutes(): void
    {
        putenv(LaravelLocalization::ENV_ROUTE_KEY.'='.config('app.locale'));

        $this->refreshApplication();
    }

    /**
     * A menu item for the same entity and view as the news item, saved with the name the admin
     * menu builder would record.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeBlogsMenuItem(string $route, array $attributes = []): MenuItem
    {
        $news = MenuItem::where('routename', self::NEWS_ROUTE)->firstOrFail();

        $menuItem = MenuItem::create([
            'language' => $news->language,
            'menu_id' => $news->menu_id,
            'title' => 'zz '.$route,
            'type' => 'entity',
            'route' => $route,
            'entity_id' => $news->entity_id,
            'entity_view_id' => $news->entity_view_id,
            'publish' => 1,
            ...$attributes,
        ]);

        $menuItem->routename = MenuRouteName::forMenuItem($menuItem, $menuItem->entity, $menuItem->entityview);
        $menuItem->save();

        return $menuItem;
    }

    private function contextOf(string $routeName): FrontRouteContext
    {
        $this->assertTrue(Route::has($routeName), 'Route '.$routeName.' is not registered.');

        $context = FrontRouteContext::forRouteName($routeName);

        $this->assertNotNull($context, 'Route '.$routeName.' carries no menu context.');

        return $context;
    }

    public function test_menu_routes_are_named_after_their_url(): void
    {
        $pages = Entity::where('resource_slug', 'pages')->firstOrFail();
        $blogs = Entity::where('resource_slug', 'blogs')->firstOrFail();
        $show = new EntityView(['method' => 'show']);
        $index = new EntityView(['method' => 'index']);

        $cases = [
            'entity.pages.about.show' => [new MenuItem(['type' => 'page', 'route' => 'about']), $pages, $show],
            'entity.pages.contact.thank-you.show' => [new MenuItem(['type' => 'page', 'route' => 'contact/thank-you']), $pages, $show],
            'entitytag.blogs.media.news.index' => [new MenuItem(['type' => 'entity', 'route' => 'media/news']), $blogs, $index],
            MenuRouteName::HOME => [new MenuItem(['type' => 'page', 'route' => null, 'is_home' => 1]), $pages, $show],
        ];

        foreach ($cases as $expected => [$menuItem, $entity, $entityView]) {
            $this->assertSame($expected, MenuRouteName::forMenuItem($menuItem, $entity, $entityView));
        }

        $this->assertNull(MenuRouteName::forMenuItem(new MenuItem(['type' => 'parent', 'route' => 'media']), null, null));
        $this->assertNull(MenuRouteName::forMenuItem(new MenuItem(['type' => 'url', 'route' => null]), null, null));
    }

    public function test_every_menu_route_knows_its_menu_item_and_has_no_id_in_its_name(): void
    {
        $checked = 0;

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $context = FrontRouteContext::forRoute($route);

            if (! $context) {
                continue;
            }

            $checked++;

            $this->assertNotNull(MenuItem::find($context->menuItemId), $route->getName().' points to a menu item that does not exist.');

            foreach (explode('.', (string) $route->getName()) as $segment) {
                $this->assertFalse(ctype_digit($segment), $route->getName().' still contains a database ID.');
            }
        }

        $this->assertGreaterThan(0, $checked, 'No menu routes found.');
    }

    public function test_the_same_entity_can_be_in_the_menu_twice(): void
    {
        $archive = $this->makeBlogsMenuItem('zz-archive');

        try {
            $this->rebuildRoutes();

            $newsContext = $this->contextOf(self::NEWS_ROUTE);
            $archiveContext = $this->contextOf('entitytag.blogs.zz-archive.index');

            $this->assertNotSame($newsContext->menuItemId, $archiveContext->menuItemId);
            $this->assertSame($archive->id, $archiveContext->menuItemId);

            // each detail route knows its own menu item, and shows one object
            $archiveShow = $this->contextOf('entitytag.blogs.zz-archive.index.show');
            $this->assertSame($archive->id, $archiveShow->menuItemId);
            $this->assertSame('show', $archiveShow->method);

            $names = collect(Route::getRoutes()->getRoutes())->map->getName()->filter();
            $this->assertSame($names->count(), $names->unique()->count(), 'Route names must stay unique.');
        } finally {
            $archive->delete();
        }
    }

    public function test_a_detail_page_knows_which_menu_item_it_belongs_to(): void
    {
        $blog = Blog::query()->whereNotNull('slug')->first();

        if (! $blog) {
            $this->markTestSkipped('No blog to show.');
        }

        $archive = $this->makeBlogsMenuItem('zz-archive');

        try {
            $this->rebuildRoutes();

            $this->get('/'.config('app.locale').'/zz-archive/'.$blog->slug.'.html')->assertOk();

            // the active menu item is the one the page was reached through, not the first match
            $activeMenu = app(FrontMenuRepository::class)->getActiveMenuArray(true);
            $this->assertContains($archive->id, $activeMenu);
            $this->assertNotContains(MenuItem::where('routename', self::NEWS_ROUTE)->value('id'), $activeMenu);

            // and its list, for the "back" link and the object links, is its own
            $activeRoute = app(FrontEntityResolver::class)->getLaraActiveRoute('entitytag.blogs.zz-archive.index');
            $this->assertSame($archive->id, $activeRoute->getMenuId());
            $this->assertSame('entitytag.blogs.zz-archive.index.show', $activeRoute->getSingleRoute());
        } finally {
            $archive->delete();
        }
    }

    public function test_a_tagged_menu_item_shows_its_objects_through_the_tagless_menu_item(): void
    {
        $tag = Tag::where('resource_slug', 'blogs')->where('route', 'tech')->first();

        if (! $tag) {
            $this->markTestSkipped('No "tech" blog tag in the test database.');
        }

        $techNews = $this->makeBlogsMenuItem('zz-tech-news', ['tag_id' => $tag->id]);

        try {
            $this->rebuildRoutes();

            $context = $this->contextOf('entitytag.blogs.zz-tech-news.index');

            $this->assertSame($techNews->id, $context->menuItemId);
            $this->assertSame(['tech'], $context->tags);
            $this->assertSame('entitytag.blogs.news.tech.index.show', $context->singleRoute);
            $this->assertTrue(Route::has($context->singleRoute), 'The single route of a tagged menu item must exist.');
        } finally {
            $techNews->delete();
        }
    }

    public function test_the_menu_context_survives_the_route_cache(): void
    {
        $compiled = Route::getRoutes()->compile();

        $cached = (new CompiledRouteCollection($compiled['compiled'], $compiled['attributes']))
            ->setRouter(app('router'))
            ->setContainer(app());

        $context = FrontRouteContext::forRoute($cached->getByName(self::NEWS_ROUTE));

        $this->assertNotNull($context);
        $this->assertSame(MenuItem::where('routename', self::NEWS_ROUTE)->value('id'), $context->menuItemId);
    }

    public function test_the_tag_links_of_a_list_come_from_its_context(): void
    {
        $activeRoute = app(FrontEntityResolver::class)->getLaraActiveRoute(self::NEWS_ROUTE);

        $this->assertSame(self::NEWS_ROUTE, $activeRoute->getMenuRoute());
        $this->assertSame('entitytag.blogs.news.tech.index', $activeRoute->getTagRoute('tech'));
        $this->assertTrue(Route::has($activeRoute->getTagRoute('tech')), 'The tag route of a list must exist.');
    }

    /**
     * lara:route:cache swaps the new cache in without removing the old one first. The fresh
     * application it reads the routes from must not load that old cache, or every run stores the
     * previous routes again and route changes never reach the site.
     */
    public function test_the_route_cache_command_stores_the_current_routes_while_a_cache_exists(): void
    {
        $cacheDirectory = storage_path('framework/testing-route-cache-'.getmypid());
        File::ensureDirectoryExists($cacheDirectory);

        $previousCachePath = $_SERVER['APP_ROUTES_CACHE'] ?? null;
        $_SERVER['APP_ROUTES_CACHE'] = $_ENV['APP_ROUTES_CACHE'] = $cacheDirectory.'/routes-v7.php';
        putenv('APP_ROUTES_CACHE='.$cacheDirectory.'/routes-v7.php');

        $archive = null;

        try {
            $this->artisan('lara:route:cache')->assertSuccessful();
            $this->assertFileExists($cacheDirectory.'/routes-v7_'.config('app.locale').'.php');

            $archive = $this->makeBlogsMenuItem('zz-archive');

            $this->artisan('lara:route:cache')->assertSuccessful();

            $this->assertStringContainsString(
                'entitytag.blogs.zz-archive.index',
                base64_decode(Str::between(File::get($cacheDirectory.'/routes-v7_'.config('app.locale').'.php'), "base64_decode('", "')")),
                'The second run must contain the menu item added after the first run.'
            );
        } finally {
            $archive?->delete();

            if ($previousCachePath === null) {
                unset($_SERVER['APP_ROUTES_CACHE'], $_ENV['APP_ROUTES_CACHE']);
                putenv('APP_ROUTES_CACHE');
            } else {
                $_SERVER['APP_ROUTES_CACHE'] = $_ENV['APP_ROUTES_CACHE'] = $previousCachePath;
                putenv('APP_ROUTES_CACHE='.$previousCachePath);
            }

            File::deleteDirectory($cacheDirectory);
        }
    }
}
