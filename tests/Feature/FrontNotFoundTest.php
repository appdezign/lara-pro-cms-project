<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Lara\Common\Models\MenuItem;
use Mcamara\LaravelLocalization\LaravelLocalization;
use Tests\TestCase;
use Usamamuneerchaudhary\FilaRank\Models\Redirect;
use Usamamuneerchaudhary\FilaRank\Redirects\RedirectManager;

/**
 * Unknown URLs and missing objects answer 404 with the full CMS error page
 * (layout and menu) on the requested URL, and redirects from seo_redirects
 * are applied before the page is resolved.
 */
class FrontNotFoundTest extends TestCase
{
    use DatabaseTransactions;

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

    public function test_an_unknown_url_is_a_404_with_the_cms_page(): void
    {
        $response = $this->get('/nl/zz-no-such-page');

        $response->assertNotFound();
        $this->assertSame('error.show.404', Route::current()?->getName());
        $this->assertShowsTheMainMenu($response->getContent());
    }

    public function test_a_missing_object_is_a_404_with_the_cms_page_on_its_own_url(): void
    {
        $item = MenuItem::langIs('nl')->where('type', 'entity')->whereNotNull('route')->with('entity')->firstOrFail();
        $suffix = $item->entity->objrel_has_terms ? '.html' : '';

        $response = $this->get('/nl/'.$item->route.'/zz-no-such-object'.$suffix);

        $response->assertNotFound();
        $this->assertShowsTheMainMenu($response->getContent());
    }

    public function test_a_redirect_is_applied(): void
    {
        Redirect::create(['from_path' => '/nl/oude-test-url', 'to_path' => '/nl/news', 'status' => 301, 'active' => true]);
        app(RedirectManager::class)->flushCache();

        $response = $this->get('/nl/oude-test-url');

        $response->assertStatus(301)->assertRedirect(url('/nl/news'));
    }

    private function assertShowsTheMainMenu(string $html): void
    {
        $item = MenuItem::langIs('nl')->where('menu_id', 1)->whereNotNull('route')->where('route', '!=', '')->firstOrFail();

        $this->assertStringContainsString('/nl/'.$item->route, $html, 'The error page has no main menu.');
    }
}
