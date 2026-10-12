<?php

namespace Tests\Feature;

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Lara\Admin\Resources\Pages\Pages\ListPages;
use Lara\Common\Models\MenuItem;
use Lara\Common\Models\Page;
use Lara\Common\Models\User;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

/**
 * A page that a menu item links to cannot be deleted: its delete button in the
 * pages list is disabled, and the model refuses the delete on every other path.
 * Deleting it would leave a menu item pointing at a trashed page.
 */
class AdminPageDeleteTest extends TestCase
{
    use DatabaseTransactions;

    public function test_the_delete_action_is_disabled_for_a_page_in_the_menu(): void
    {
        $this->actingAsAdmin();
        $page = $this->makePage();
        $this->addToMenu($page);

        Livewire::test(ListPages::class)
            ->assertActionDisabled(TestAction::make('delete')->table($page));
    }

    public function test_the_delete_action_is_disabled_for_a_page_with_an_unpublished_menu_item(): void
    {
        $this->actingAsAdmin();
        $page = $this->makePage();
        $this->addToMenu($page, publish: 0);

        Livewire::test(ListPages::class)
            ->assertActionDisabled(TestAction::make('delete')->table($page));
    }

    public function test_a_page_that_is_not_in_the_menu_can_be_deleted_from_the_list(): void
    {
        $this->actingAsAdmin();
        $page = $this->makePage();

        Livewire::test(ListPages::class)
            ->assertActionEnabled(TestAction::make('delete')->table($page))
            ->callAction(TestAction::make('delete')->table($page));

        $this->assertSoftDeleted($page);
    }

    public function test_the_model_refuses_to_delete_a_page_in_the_menu(): void
    {
        $page = $this->makePage();
        $this->addToMenu($page);

        try {
            $page->delete();
            $this->fail('Deleting a page in the menu should throw.');
        } catch (LogicException) {
            $this->assertNotSoftDeleted($page);
        }
    }

    public function test_the_model_refuses_to_force_delete_a_page_in_the_menu(): void
    {
        $page = $this->makePage();
        $this->addToMenu($page);

        $this->expectException(LogicException::class);

        $page->forceDelete();
    }

    private function makePage(): Page
    {
        return Page::factory()->create(['title' => 'Zz delete test', 'language' => 'nl']);
    }

    private function addToMenu(Page $page, int $publish = 1): MenuItem
    {
        $menuId = MenuItem::langIs('nl')->value('menu_id');

        if (! $menuId) {
            $this->markTestSkipped('No Dutch menu in the current database.');
        }

        return MenuItem::create([
            'language' => 'nl',
            'menu_id' => $menuId,
            'title' => 'zz delete test',
            'type' => 'page',
            'route' => 'zz-delete-test',
            'object_id' => $page->id,
            'publish' => $publish,
        ]);
    }

    private function actingAsAdmin(): User
    {
        $user = User::where('name', 'admin')->first();

        if (! $user) {
            $this->markTestSkipped('No "admin" user in the current database.');
        }

        $this->actingAs($user);
        Filament::setCurrentPanel('admin');

        return $user;
    }
}
