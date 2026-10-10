<?php

namespace Lara\App\Legacy\Navigation;

use Closure;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Route;
use Lara\Admin\Contracts\PanelExtension;
use Lara\App\Legacy\Http\Controllers\CustomBlogController;

/**
 * Menu items and routes of the legacy (non-Livewire) admin pages.
 *
 * Switched on by listing this class in config('lara-admin.panel_extensions').
 */
class LegacyNavigation implements PanelExtension
{
    /**
     * @return array<NavigationItem>
     */
    public function navigationItems(): array
    {
        return [
            NavigationItem::make('Custom Blogs')
                ->url(fn (): string => route('filament.admin.custom-blog.index'))
                ->group('Custom')
                ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.custom-blog.*'))
                ->sort(4),
        ];
    }

    public function routes(): ?Closure
    {
        return function (): void {
            Route::resource('custom-blog', CustomBlogController::class)
                ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
                ->parameters(['custom-blog' => 'blog']);
        };
    }
}
