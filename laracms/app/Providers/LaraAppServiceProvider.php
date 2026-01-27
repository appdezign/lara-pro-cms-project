<?php

namespace Lara\App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;


use Lara\Common\Models\Entity;

use Lara\Common\Http\Controllers\Setup\Concerns\HasSetup;

use Mcamara\LaravelLocalization\Traits\LoadsTranslatedCachedRoutes;

class LaraAppServiceProvider extends ServiceProvider
{

	use HasSetup;
	use LoadsTranslatedCachedRoutes;


	/**
	 * Bootstrap the module services.
	 *
	 * @return void
	 */
	public function boot()
	{

		// see: https://github.com/mcamara/laravel-localization?tab=readme-ov-file#caching-routes
		RouteServiceProvider::loadCachedRoutesUsing(fn () => $this->loadCachedRoutes());

		// Load Translations
		$this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'lara-app');

		// Views
		$this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'lara-app');


		// Load policies
		if (!$this->laraNeedsSetup() && !App::runningInConsole()) {
			$entities = Entity::whereNotNull('policy')->get();
			foreach ($entities as $entity) {
				Gate::policy($entity->model_class, $entity->policy);
			}
			// dd(Gate::policies());
		}
	}

	/**
	 * Register the module services.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}
}
