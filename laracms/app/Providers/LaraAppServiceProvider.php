<?php

namespace Lara\App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

use Lara\Common\Models\Entity;

class LaraAppServiceProvider extends ServiceProvider
{
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
		$entities = Entity::whereNotNull('policy')->get();
		foreach ($entities as $entity) {
			Gate::policy($entity->model_class, $entity->policy);
		}
		// dd(Gate::policies());
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
