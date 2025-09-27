<?php

namespace Lara\App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

use Lara\Common\Models\Entity;

use Lara\Common\Http\Controllers\Setup\Concerns\HasSetup;

class LaraAppServiceProvider extends ServiceProvider
{

	use HasSetup;
	/**
	 * Bootstrap the module services.
	 *
	 * @return void
	 */
	public function boot()
	{

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
