<?php

namespace Lara\App\Filament\Navigation;

use Closure;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Route;
use Lara\App\Http\Controllers\Admin\CustomBlogController;

trait HasCustomNavigation
{
    public static function getCustomNavigation()
    {
        $rows = [];

        if (config('lara-admin.has_custom_routes')) {
            $rows[] = NavigationItem::make('Custom Blogs')
                ->url(fn (): string => route('filament.admin.custom-blog.index'))
                ->group('Custom')
                ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.custom-blog.*'))
                ->sort(4);
        }

        return $rows;
    }

    /**
     * Custom (non-Livewire) admin routes.
     *
     * These are registered inside the admin panel with its middleware and auth middleware,
     * under the panel path (`/admin`) and the route name prefix `filament.admin.`.
     */
    public static function getCustomRoutes(): ?Closure
    {
        if (! config('lara-admin.has_custom_routes')) {
            return null;
        }

        return function (): void {
            Route::resource('custom-blog', CustomBlogController::class)
                ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
                ->parameters(['custom-blog' => 'blog']);
        };
    }
}
