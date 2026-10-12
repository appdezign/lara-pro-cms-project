<?php

namespace Lara\App\Legacy\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Lara\App\Legacy\Models\CustomBlog;
use Lara\App\Legacy\Policies\CustomBlogPolicy;

/**
 * Services of the legacy (non-Livewire) admin pages in laracms/app/Legacy.
 *
 * Their menu items and routes are added by LegacyNavigation (config lara-admin.panel_extensions).
 */
class LegacyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'lara-legacy');

        // Legacy models are not entities, so their policies are registered here,
        // also in the console, so the Role screen and the tests see them
        Gate::policy(CustomBlog::class, CustomBlogPolicy::class);
    }
}
