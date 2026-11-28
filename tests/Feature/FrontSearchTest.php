<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Lara\Admin\Enums\MenuItemType;
use Lara\App\Models\Blog;
use Lara\Common\Models\MenuItem;
use Lara\Common\Models\Page;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Tests\TestCase;

/**
 * The front search (Special\SearchController):
 *  - only pages that a menu links are found, as v8: the comparison used 'page' while the pages
 *    entity is 'pages', so the error page ("Oops", slug 404) and module pages were listed too;
 *  - the text custom fields are searched along with title, lead and body (gain: a page lead in
 *    the custom field "intro" was not found);
 *  - a page result links to its menu url (searchurl): pages have no detail route, so the themes'
 *    route($result->routename, ['slug' => ...]) gave "/nl?slug=..." (the home page).
 *
 * The routing locale is set before the application is created (see GoogleSitemapGeneratorTest),
 * so the writes stay inside the transaction.
 */
class FrontSearchTest extends TestCase
{
    use DatabaseTransactions;

    private string|false $routeLocale;

    protected function setUp(): void
    {
        $this->routeLocale = getenv(LaravelLocalization::ENV_ROUTE_KEY);
        putenv(LaravelLocalization::ENV_ROUTE_KEY.'=nl');

        parent::setUp();

        $this->assertGreaterThan(0, DB::transactionLevel(), 'These tests write: they must run inside a transaction.');

        if (! Route::has('special.search.result')) {
            $this->markTestSkipped('No search route registered.');
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        putenv($this->routeLocale === false ? LaravelLocalization::ENV_ROUTE_KEY : LaravelLocalization::ENV_ROUTE_KEY.'='.$this->routeLocale);
    }

    public function test_a_page_without_a_menu_route_is_not_found(): void
    {
        $inMenu = Page::factory()->create(['language' => 'nl', 'publish' => 1, 'title' => 'Zzsearchpage in menu', 'menuroute' => 'zz-in-menu']);
        $notInMenu = Page::factory()->create(['language' => 'nl', 'publish' => 1, 'title' => 'Zzsearchpage error', 'menuroute' => null]);

        $ids = $this->found('Zzsearchpage', 'pages');

        $this->assertContains($inMenu->id, $ids);
        $this->assertNotContains($notInMenu->id, $ids, 'A page no menu links (the error page, a module page).');
    }

    public function test_a_text_custom_field_is_searched(): void
    {
        $field = DB::table('lara_resource_entity_custom_fields')
            ->join('lara_resource_entities', 'lara_resource_entities.id', '=', 'lara_resource_entity_custom_fields.entity_id')
            ->where('lara_resource_entities.resource_slug', 'blogs')
            ->whereIn('field_type', ['string', 'text', 'textarea', 'richeditor', 'richeditormin'])
            ->value('field_name') ?? $this->markTestSkipped('No text custom field on blogs.');

        $blog = Blog::factory()->create(['language' => 'nl', 'publish' => 1, 'publish_to' => null, $field => 'zzcustomsearchvalue']);

        $this->assertContains($blog->id, $this->found('zzcustomsearchvalue', 'blogs'), "All results: the custom field {$field}.");
        $this->assertContains($blog->id, $this->found('zzcustomsearchvalue', 'blogs', 'blogs'), "One module's results: the custom field {$field}.");
    }

    public function test_a_page_result_links_to_its_menu_url(): void
    {
        $home = MenuItem::where('type', MenuItemType::Page->value)->where('language', 'nl')->where('is_home', 1)->first()
            ?? $this->markTestSkipped('No Dutch home page in a menu in the test database.');
        $page = Page::factory()->create(['language' => 'nl', 'publish' => 1, 'title' => 'Zzsearchlink page', 'menuroute' => 'zz-search-link']);
        $item = $home->replicate();
        $item->fill(['is_home' => 0, 'route' => 'zz-search-link', 'object_id' => $page->id, 'title' => 'Zz search link']);
        $item->saveQuietly();

        $response = $this->get('/nl/searchresult?keywords=Zzsearchlink');

        $result = $response->viewData('data')->results->pages->objects->firstWhere('id', $page->id) ?? $this->fail('The page is not found.');
        $this->assertSame(url('nl/zz-search-link'), $result->searchurl);
        $response->assertSee('href="'.url('nl/zz-search-link').'"', false);
        $response->assertDontSee('?slug='.$page->slug, false);
    }

    /**
     * The ids found for a resource.
     *
     * @return array<int, int>
     */
    private function found(string $keywords, string $resourceSlug, ?string $resource = null): array
    {
        $path = '/nl/searchresult'.($resource ? '/'.$resource : '').'?keywords='.urlencode($keywords);

        $response = $this->get($path);
        $response->assertOk();
        $this->assertStringStartsWith('special.search', Route::current()?->getName() ?? '(none)');

        $results = $response->viewData('data')->results;

        if (! isset($results->$resourceSlug)) {
            $this->fail("No {$resourceSlug} in the search results of {$path}: is it in the main menu?");
        }

        return $results->$resourceSlug->objects->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
