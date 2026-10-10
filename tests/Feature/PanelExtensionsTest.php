<?php

namespace Tests\Feature;

use Filament\Panel;
use InvalidArgumentException;
use Lara\Admin\Providers\AdminPanelProvider;
use stdClass;
use Tests\TestCase;

/**
 * The admin panel adds the navigation items and routes of the classes in
 * lara-admin.panel_extensions. A listed class that is not a PanelExtension must fail loudly,
 * instead of silently dropping its menu items and routes.
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
}
