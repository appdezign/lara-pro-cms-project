<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Lara\Admin\Resources\MenuItems\Pages\CreateMenuItem;
use Lara\Admin\Resources\MenuItems\Pages\EditMenuItem;
use Lara\Common\Models\Menu;
use Lara\Common\Models\MenuItem;
use Lara\Common\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The menu of a menu item. Menu items are a nested set per menu and language,
 * so an item cannot move to another menu by changing menu_id: its tree
 * position would overlap the other menu's tree. The menu is chosen when the
 * item is created and is read-only afterwards.
 */
class AdminMenuItemFormTest extends TestCase
{
    use DatabaseTransactions;

    public function test_a_new_menu_item_needs_a_menu(): void
    {
        $this->actingAsAdmin();

        Livewire::test(CreateMenuItem::class)
            ->fillForm(['menu_id' => null])
            ->call('create')
            ->assertHasFormErrors(['menu_id' => 'required']);
    }

    public function test_the_menu_of_an_existing_menu_item_cannot_be_changed(): void
    {
        $this->actingAsAdmin();
        $menuItem = MenuItem::langIs('nl')->whereNotNull('menu_id')->firstOrFail();

        Livewire::test(EditMenuItem::class, ['record' => $menuItem->id])
            ->assertFormFieldDisabled('menu_id');
    }

    public function test_the_parent_options_only_list_items_of_the_chosen_menu(): void
    {
        $this->actingAsAdmin();
        $mainMenuItem = MenuItem::langIs('nl')->whereNotNull('menu_id')->firstOrFail();
        $otherMenu = Menu::forceCreate(['title' => 'zz other menu', 'slug' => 'zz-other-menu']);
        $otherMenuItem = MenuItem::create(['language' => 'nl', 'menu_id' => $otherMenu->id, 'title' => 'zz other item', 'type' => 'parent', 'publish' => 1]);

        Livewire::test(CreateMenuItem::class)
            ->fillForm(['menu_id' => $mainMenuItem->menu_id])
            ->assertFormFieldExists('parent_id', fn (Select $field): bool => array_key_exists($mainMenuItem->id, $field->getOptions())
                && ! array_key_exists($otherMenuItem->id, $field->getOptions()));
    }

    public function test_an_item_cannot_get_one_of_its_own_children_as_parent(): void
    {
        $this->actingAsAdmin();
        $menuId = MenuItem::langIs('nl')->whereNotNull('menu_id')->value('menu_id');
        $item = MenuItem::create(['language' => 'nl', 'menu_id' => $menuId, 'title' => 'zz section', 'type' => 'parent', 'publish' => 1]);
        $child = MenuItem::create(['language' => 'nl', 'menu_id' => $menuId, 'title' => 'zz child', 'type' => 'parent', 'publish' => 1]);
        $child->appendToNode($item)->save();

        Livewire::test(EditMenuItem::class, ['record' => $item->id])
            ->assertFormFieldExists('parent_id', fn (Select $field): bool => ! array_key_exists($child->id, $field->getOptions())
                && ! array_key_exists($item->id, $field->getOptions()));
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::where('name', 'admin')->firstOrFail());
        Filament::setCurrentPanel('admin');
    }
}
