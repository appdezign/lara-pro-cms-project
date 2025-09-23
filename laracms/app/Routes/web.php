<?php

use Illuminate\Support\Facades\App;

use Spatie\Honeypot\ProtectAgainstSpam;

use Lara\Common\Models\MenuItem;
use Lara\Common\Models\Entity;
use Lara\Common\Models\Tag;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

if (!App::runningInConsole() && !config('lara.needs_setup')) {

	// FRONT Entity Routes

	Route::group(['prefix' => LaravelLocalization::setLocale(), 'middleware' => ['web', 'httpcache', 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'dateLocale']], function () {

		$entity_prefix = 'entity';
		$entity_tag_prefix = 'entitytag';

		$locale = LaravelLocalization::getCurrentLocale();

		// get home
		$rootMenuItem = MenuItem::langIs($locale)
			->menuSlugIs('main')
			->whereNull('parent_id')
			->isHome()
			->with('entity')
			->first();

		if ($rootMenuItem) {

			/* ~~~~~~~~~~~~ DYNAMIC ROUTE MIDDLEWARE (start) ~~~~~~~~~~~~ */
			$specialMiddleware = array();
			if ((isset($rootMenuItem->entity) && $rootMenuItem->entity->has_front_auth) == 1 || $rootMenuItem->route_has_auth) {
				$specialMiddleware[] = 'auth';
			}
			if (config('app.env') == 'production' && config('httpcache.enabled')) {
				$specialMiddleware[] = 'ttl:' . config('lara.httpcache_ttl');
			}
			/* ~~~~~~~~~~~~ DYNAMIC ROUTE MIDDLEWARE (end) ~~~~~~~~~~~~ */

			// Home
			Route::get('/', 'Front\Page\HomeController@show')->name('special.home.show')->middleware($specialMiddleware);

		}

		/**
		 * Get all Pages from the MENU
		 * and create named routes for single page objects.
		 */
		$menuPages = MenuItem::langIs($locale)
			->typeIs('page')
			->with('entity')
			->with('entityview')
			->get();

		foreach ($menuPages as $menuPage) {
			if (!empty($menuPage->route)) {

				/* ~~~~~~~~~~~~ DYNAMIC ROUTE MIDDLEWARE (start) ~~~~~~~~~~~~ */
				$menuPageMiddleware = array();
				if ($menuPage->entity->has_front_auth == 1 || $menuPage->route_has_auth) {
					$menuPageMiddleware[] = 'auth';
				}
				if (config('app.env') == 'production' && config('httpcache.enabled')) {
					$menuPageMiddleware[] = 'ttl:' . config('lara.httpcache_ttl');
				}
				/* ~~~~~~~~~~~~ DYNAMIC ROUTE MIDDLEWARE (end) ~~~~~~~~~~~~ */

				$Ctrlr = 'Front\Page\\' . $menuPage->entity->controller;

				Route::get($menuPage->route, $Ctrlr . '@' . $menuPage->entityview->method)
					->name($menuPage->routename)->middleware($menuPageMiddleware);
			}
		}

		/**
		 * Get all entities from the MENU
		 * and create named routes for lists and single objects
		 */
		$menuItems = MenuItem::langIs($locale)
			->typeIs('entity')
			->with('entity')
			->with('entityview')
			->get();

		foreach ($menuItems as $menuItem) {

			// entities and forms only
			if (in_array($menuItem->entity->cgroup, ['entity', 'form'])) {

				/* ~~~~~~~~~~~~ DYNAMIC ROUTE MIDDLEWARE (start) ~~~~~~~~~~~~ */
				$menuItemMiddleware = array();
				if ($menuItem->entity->has_front_auth == 1 || $menuItem->route_has_auth) {
					$menuItemMiddleware[] = 'auth';
				}
				if (config('app.env') == 'production' && config('httpcache.enabled')) {
					$menuItemMiddleware[] = 'ttl:' . config('lara.httpcache_ttl');
				}
				/* ~~~~~~~~~~~~ DYNAMIC ROUTE MIDDLEWARE (end) ~~~~~~~~~~~~ */

				$Ctrlr = 'Front\\Entity\\' . $menuItem->entity->controller;

				if ($menuItem->entity->objrel_has_terms == 1) {

					if (empty($menuItem->tag_id)) {

						// SEO Routes for tags
						Route::get($menuItem->route, $Ctrlr . '@' . $menuItem->entityview->method)
							->name($menuItem->routename)->middleware($menuItemMiddleware);

						// add .html to object slug, so we can distinguish between an object slug and a (sub)cat slug.
						Route::get($menuItem->route . '/{slug}.html', $Ctrlr . '@show')
							->name($menuItem->routename . '.show')->middleware($menuItemMiddleware);

						$tags = Tag::resourceIs($menuItem->entity->resource_slug)->whereNotNull('route')->get();

						foreach ($tags as $tag) {

							$tagslug = str_replace('.', '/', $tag->route);

							Route::get($menuItem->route . '/' . $tagslug, $Ctrlr . '@' . $menuItem->entityview->method)
								->name($entity_tag_prefix . '.' . $menuItem->entity->resource_slug . '.' . $tag->route . '.' . $menuItem->entityview->method)->middleware($menuItemMiddleware);

							Route::get($menuItem->route . '/' . $tagslug . '/{slug}.html', $Ctrlr . '@show')
								->name($entity_tag_prefix . '.' . $menuItem->entity->resource_slug . '.' . $tag->route . '.' . $menuItem->entityview->method . '.show')->middleware($menuItemMiddleware);

						}

					} else {

						// entity with a tag (no show method, no tags)
						Route::get($menuItem->route, $Ctrlr . '@' . $menuItem->entityview->method)
							->name($menuItem->routename)->middleware($menuItemMiddleware);

					}

				} else {

					Route::get($menuItem->route, $Ctrlr . '@' . $menuItem->entityview->method)
						->name($menuItem->routename)->middleware($menuItemMiddleware);

				}
			}
		}

		/**
		 * Get all FORMS from the MENU
		 * and create named routes
		 */
		$menuForms = MenuItem::langIs($locale)
			->typeIs('form')
			->with('entity')
			->with('entityview')
			->get();

		foreach ($menuForms as $menuForm) {

			// exclude CUSTOM entities
			if ($menuForm->entity->cgroup != 'entity') {

				/* ~~~~~~~~~~~~ DYNAMIC ROUTE MIDDLEWARE (start) ~~~~~~~~~~~~ */
				$menuFormMiddleware = array();
				if ($menuForm->entity->has_front_auth == 1 || $menuForm->route_has_auth) {
					$menuFormMiddleware[] = 'auth';
				}
				if (config('app.env') == 'production' && config('lara.httpcache_on_forms')) {
					$menuFormMiddleware[] = 'ttl:' . config('lara.httpcache_ttl');
				}
				/* ~~~~~~~~~~~~ DYNAMIC ROUTE MIDDLEWARE (end) ~~~~~~~~~~~~ */

				$Ctrlr = 'Front\\Form\\' . $menuForm->entity->controller;

				Route::get($menuForm->route, $Ctrlr . '@' . $menuForm->entityview->method)
					->name($menuForm->routename)->middleware($menuFormMiddleware);

				// create route for regular POST without AJAX
				Route::post($menuForm->route, $Ctrlr . '@process')
					->name('form.' . $menuForm->entity->resource_slug . '.process')
					->middleware([ProtectAgainstSpam::class, 'throttle:10,86400']); // patch 6.2.23
			}

		}

		Route::group(['prefix' => 'ajax'], function () use ($menuForms) {

			foreach ($menuForms as $menuForm) {

				// exclude CUSTOM entities
				if ($menuForm->entity->cgroup != 'entity') {

					$Ctrlr = 'Front\\Form\\' . $menuForm->entity->controller;

					// Add AJAX route
					Route::get($menuForm->entity->resource_slug, $Ctrlr . '@redirect')->name('ajax.' . $menuForm->entity->resource_slug . '.redirect');
					Route::post($menuForm->entity->resource_slug, $Ctrlr . '@process')
						->name('ajax.' . $menuForm->entity->resource_slug . '.process')
						->middleware([ProtectAgainstSpam::class, 'throttle:10,86400']); // patch 6.2.23
				}

			}

		});

		/**
		 * Fixed Urls structure (fallback)
		 *
		 * Using a fixed prefix, we can reach all entities and entity objects
		 * without using the user-defined menu
		 */
		Route::group(['prefix' => 'content'], function () {

			// These prefixes are used for the route NAMES, and NOT the URI path
			$content_prefix = 'content';
			$content_tag_prefix = 'contenttag';

			// Page Routes
			$entities = Entity::where('cgroup', 'page')->get();
			foreach ($entities as $entity) {

				/* ~~~~~~~~~~~~ DYNAMIC ROUTE MIDDLEWARE (start) ~~~~~~~~~~~~ */
				$entityMiddleware = array();
				if ($entity->has_front_auth == 1) {
					$entityMiddleware[] = 'auth';
				}
				/* ~~~~~~~~~~~~ DYNAMIC ROUTE MIDDLEWARE (end) ~~~~~~~~~~~~ */

				$Ctrlr = 'Front\Page\\' . $entity->controller;
				Route::get($entity->resource_slug . '/{id}', $Ctrlr . '@show')
					->name($content_prefix . '.' . $entity->resource_slug . '.show')->middleware($entityMiddleware);

			}

			// Entity Routes
			$entities = Entity::where('cgroup', 'entity')->get();

			foreach ($entities as $entity) {

				/* ~~~~~~~~~~~~ DYNAMIC ROUTE MIDDLEWARE (start) ~~~~~~~~~~~~ */
				$entityMiddleware = array();
				if ($entity->has_front_auth == 1) {
					$entityMiddleware[] = 'auth';
				}
				/* ~~~~~~~~~~~~ DYNAMIC ROUTE MIDDLEWARE (end) ~~~~~~~~~~~~ */

				$Ctrlr = 'Front\\Entity\\' . $entity->controller;

				if ($entity->objrel_has_terms == 1) {

					Route::get($entity->resource_slug, $Ctrlr . '@index')
						->name($content_tag_prefix . '.' . $entity->resource_slug . '.index')->middleware($entityMiddleware);

					// add .html to slug, so we can distinguish between an object slug and a (sub)cat slug.
					Route::get($entity->resource_slug . '/{id}.html', $Ctrlr . '@show')
						->name($content_tag_prefix . '.' . $entity->resource_slug . '.index.show')->middleware($entityMiddleware);

					$tags = Tag::resourceIs($entity->resource_slug)->whereNotNull('route')->get();

					foreach ($tags as $tag) {

						$tagroutename = str_replace('/', '.', $tag->route);

						Route::get($entity->resource_slug . '/' . $tag->route, $Ctrlr . '@index')
							->name($content_tag_prefix . '.' . $entity->resource_slug . '.' . $tagroutename . '.index')->middleware($entityMiddleware);
						Route::get($entity->resource_slug . '/' . $tag->route . '/{id}.html', $Ctrlr . '@show')
							->name($content_tag_prefix . '.' . $entity->resource_slug . '.' . $tagroutename . '.index.show')->middleware($entityMiddleware);

					}

				} else {

					Route::get($entity->resource_slug, $Ctrlr . '@index')
						->name($content_prefix . '.' . $entity->resource_slug . '.index')->middleware($entityMiddleware);
					Route::get($entity->resource_slug . '/{id}', $Ctrlr . '@show')
						->name($content_prefix . '.' . $entity->resource_slug . '.index.show')->middleware($entityMiddleware);

				}

			}

		});

		// 404
		Route::get('/{any}', '\Lara\App\Http\Controllers\Front\Error\AppErrorController@show')->where('any', '.*')->name('error.show.404');

	});

}



