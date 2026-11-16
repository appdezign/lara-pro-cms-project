<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Lara\Admin\Resources\Pages\Pages\ListPages;
use Lara\Common\Models\MenuItem;
use Lara\Common\Models\Page;
use Lara\Common\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Opening the Pages list syncs the menu position, route and home flag of the
 * pages of the content language, and must not fail or write needlessly.
 */
class AdminPageListSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::where('name', 'admin')->first();

        if (! $user) {
            $this->markTestSkipped('No "admin" user in the current database.');
        }

        $this->actingAs($user);
        Filament::setCurrentPanel('admin');
    }

    public function test_the_list_opens_with_a_menu_item_whose_page_is_gone(): void
    {
        $this->addToMenu(999999, 'zz-page-is-gone');

        Livewire::test(ListPages::class)->assertOk();
    }

    public function test_a_menu_page_gets_the_route_of_its_menu_item(): void
    {
        $page = Page::factory()->create(['language' => 'nl']);
        $this->addToMenu($page->id, 'zz-sync-test');

        Livewire::test(ListPages::class);

        $this->assertSame('/zz-sync-test', $page->fresh()->menuroute);
    }

    public function test_an_unchanged_page_is_not_written(): void
    {
        Livewire::test(ListPages::class);

        $page = Page::langIs('nl')->firstOrFail();
        $past = Carbon::parse('2020-01-01 00:00:00');
        Page::whereKey($page->id)->toBase()->update(['updated_at' => $past]);

        Livewire::test(ListPages::class);

        $this->assertTrue($past->equalTo($page->fresh()->updated_at));
    }

    public function test_the_pages_of_another_language_are_left_alone(): void
    {
        $page = Page::factory()->create(['language' => 'en']);
        Page::whereKey($page->id)->toBase()->update(['position' => 0]);

        Livewire::test(ListPages::class);

        $this->assertSame(0, (int) $page->fresh()->position);
    }

    private function addToMenu(int $pageId, string $route): MenuItem
    {
        $menuId = MenuItem::langIs('nl')->value('menu_id');

        if (! $menuId) {
            $this->markTestSkipped('No Dutch menu in the current database.');
        }

        return MenuItem::create([
            'language' => 'nl',
            'menu_id' => $menuId,
            'title' => $route,
            'type' => 'page',
            'route' => $route,
            'object_id' => $pageId,
            'publish' => 1,
        ]);
    }
}
