<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Lara\Admin\Enums\MenuItemType;
use Lara\Common\Models\MenuItem;
use Lara\Common\Models\ObjectRelated;
use Lara\Common\Models\Page;
use Lara\Front\Services\FrontEntityResolver;
use Lara\Front\Services\FrontObjectRepository;
use Lara\Front\Services\FrontRouteResolver;
use Tests\TestCase;

/**
 * A related page links to its menu url (FrontObjectRepository::getFrontRelated), the url that
 * content.pages.show redirects to (FrontRouteResolver::getPageMenuUrl), instead of the preview
 * route and its redirect. A page no menu links keeps the preview route.
 */
class FrontRelatedPagesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_a_related_page_in_a_menu_links_to_its_menu_url(): void
    {
        [$source, $inMenu, $notInMenu] = $this->pages();

        ObjectRelated::create([
            'entity_type' => Page::class,
            'entity_id' => $source->id,
            'related_page_objects' => [['page_object_id' => $inMenu->id], ['page_object_id' => $notInMenu->id]],
            'related_entity_objects' => [],
            'related_entities' => [],
        ]);

        $related = collect(app(FrontObjectRepository::class)->getFrontRelated($this->pagesEntity(), $source->id))->keyBy('title');

        $this->assertSame(url('nl/zz-related-page'), $related[$inMenu->title]->url);
        $this->assertSame('content.pages.show', $related[$inMenu->title]->route, 'The route stays: themes tell a page by it.');

        $this->assertNull($related[$notInMenu->title]->url);
        $this->assertSame('content.pages.show', $related[$notInMenu->title]->route);
        $this->assertSame(['id' => $notInMenu->id], $related[$notInMenu->title]->params);
    }

    public function test_the_page_menu_url_is_the_redirect_target(): void
    {
        [, $inMenu, $notInMenu] = $this->pages();

        $resolver = app(FrontRouteResolver::class);

        $this->assertSame('nl/zz-related-page', $resolver->getPageMenuUrl($inMenu));
        $this->assertNull($resolver->getPageMenuUrl($notInMenu));
    }

    /**
     * A source page, a page with a menu item (a copy of the home item) and a page without one.
     *
     * @return array{Page, Page, Page}
     */
    private function pages(): array
    {
        $home = MenuItem::where('type', MenuItemType::Page->value)->where('language', 'nl')->where('is_home', 1)->first()
            ?? $this->markTestSkipped('No Dutch home page in a menu in the test database.');

        $source = Page::factory()->create(['language' => 'nl', 'title' => 'Zz related source']);
        $inMenu = Page::factory()->create(['language' => 'nl', 'title' => 'Zz related in menu']);
        $notInMenu = Page::factory()->create(['language' => 'nl', 'title' => 'Zz related not in menu']);

        $item = $home->replicate();
        $item->fill(['is_home' => 0, 'route' => 'zz-related-page', 'object_id' => $inMenu->id, 'title' => 'Zz related']);
        $item->saveQuietly();

        return [$source, $inMenu, $notInMenu];
    }

    private function pagesEntity(): object
    {
        $entity = app(FrontEntityResolver::class)->getResourceBySlug('pages') ?? $this->markTestSkipped('No pages entity.');

        if (! $entity->hasRelated()) {
            $this->markTestSkipped('Pages have no related objects in the test database.');
        }

        return $entity;
    }
}
