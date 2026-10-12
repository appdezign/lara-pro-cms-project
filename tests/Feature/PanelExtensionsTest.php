<?php

namespace Tests\Feature;

use Filament\Panel;
use InvalidArgumentException;
use Lara\Admin\Providers\AdminPanelProvider;
use Lara\App\Filament\Enums\CustomNavGroup;
use Lara\App\Legacy\Navigation\LegacyNavigation;
use stdClass;
use Tests\TestCase;

/**
 * The admin panel adds the navigation items and routes of the classes in
 * lara-admin.panel_extensions, with their navigation groups after the core groups. A listed class
 * that is not a PanelExtension must fail loudly, instead of silently dropping its menu items and routes.
 */
class PanelExtensionsTest extends TestCase
{
    public function test_a_panel_extension_that_does_not_implement_the_contract_throws(): void
    {
        config(['lara-admin.panel_extensions' => [stdClass::class]]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Panel extension [stdClass] must implement Lara\Admin\Contracts\PanelExtension.');

        (new AdminPanelProvider($this->app))->panel(Panel::make());
    }

    public function test_the_navigation_groups_of_a_panel_extension_are_listed_last_with_their_icon(): void
    {
        config(['lara-admin.panel_extensions' => [LegacyNavigation::class]]);

        $panel = (new AdminPanelProvider($this->app))->panel(Panel::make());

        $lastGroup = last($panel->getNavigationGroups());

        $this->assertSame(CustomNavGroup::Custom->getLabel(), $lastGroup->getLabel());
        $this->assertSame('bi-box', $lastGroup->getIcon());
    }
}
