<?php

use Illuminate\Support\Facades\Log;
use Lara\Common\Models\Entity;
use Lara\Common\Models\MenuItem;
use Lara\Common\Models\Tag;
use Spatie\Honeypot\ProtectAgainstSpam;
use Lara\Admin\Http\Middleware\FilamentAuthenticate;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

$tablename = config('lara-common.database.ent.entities');
$laraNeedsSetup = !Schema::hasTable($tablename) || DB::table($tablename)->count() == 0;

/**
 * Resolve a controller action for a DB-configured entity controller.
 *
 * `entity.controller` is an admin-editable string. Without this guard a stale or
 * mistyped value throws while the route file is being evaluated, which takes down
 * every route on the site rather than the one route that is misconfigured.
 *
 * Optional actions (show, redirect) are absent on plenty of controllers by
 * design, so a missing one is skipped silently. A missing required action is
 * a real misconfiguration and is logged.
 *
 * @return string|null Controller action relative to the route namespace, or null
 */
$laraControllerAction = static function (string $group, ?string $controller, ?string $method, bool $optional = false): ?string {

	if (empty($controller) || empty($method)) {
		if (!$optional) {
			Log::warning('lara route: entity is missing a controller or method', [
				'group'      => $group,
				'controller' => $controller,
				'method'     => $method,
			]);
		}

		return null;
	}

	$fqcn = 'Lara\\App\\Http\\Controllers\\' . $group . '\\' . $controller;

	if (!class_exists($fqcn)) {
		Log::warning('lara route: controller class not found, skipping route', [
			'class' => $fqcn,
		]);

		return null;
	}

	if (!method_exists($fqcn, $method)) {
		if (!$optional) {
			Log::warning('lara route: controller method not found, skipping route', [
				'class'  => $fqcn,
				'method' => $method,
			]);
		}

		return null;
	}

	return $group . '\\' . $controller . '@' . $method;

};

/**
 * A menu item can only produce routes when its entity and view rows still exist.
 */
$laraMenuItemIsRoutable = static function (?MenuItem $menuItem, bool $needsView = true): bool {

	if (!$menuItem || empty($menuItem->route)) {
		return false;
	}

	if (!$menuItem->entity) {
		Log::warning('lara route: menu item references a missing entity', [
			'menu_item_id' => $menuItem->id,
			'entity_id'    => $menuItem->entity_id,
		]);

		return false;
	}

	if ($needsView && !$menuItem->entityview) {
		Log::warning('lara route: menu item references a missing entity view', [
			'menu_item_id'   => $menuItem->id,
			'entity_view_id' => $menuItem->entity_view_id,
		]);

		return false;
	}

	return true;

};

/**
 * Middleware for a dynamically registered front route.
 *
 * The /content/ fallback routes are deliberately not response-cached, so that
 * block passes $allowResponseCache = false.
 *
 * @return list<string>
 */
$laraRouteMiddleware = static function (?Entity $entity, ?MenuItem $menuItem = null, bool $allowResponseCache = true): array {

	$middleware = [];

	if (($entity && $entity->has_front_auth == 1) || ($menuItem && $menuItem->route_has_auth)) {
		$middleware[] = 'auth';
	}

	if ($allowResponseCache && config('app.env') == 'production' && config('responsecache.enabled')) {
		$middleware[] = 'cacheResponse';
	}

	return $middleware;

};

if (!$laraNeedsSetup) {

	// Custom Non-Livewire
	Route::group(['prefix' => 'admin', 'middleware' => ['web', FilamentAuthenticate::class]], function () {
		// Custom resource routes
		Route::resource('custom-blog', 'Admin\CustomBlogController', ['as' => 'admin', 'parameters' => ['custom-blog' => 'id']]);
	});

	// quick cache clear (authenticated panel users only)
	Route::post('cc', 'Front\Special\CacheController@process')
		->name('special.cache.clear')
		->middleware(['web', FilamentAuthenticate::class]);

	// external uptime monitoring
	Route::get('uptime', 'Front\Special\UptimeController@show')
		->name('special.uptime.show');

	// Front Profile
	Route::get('user/profile', 'Front\Auth\ProfileController@form')->name('special.user.profile')->middleware('auth');
	Route::patch('user/profile', 'Front\Auth\ProfileController@process')->name('special.user.saveprofile')->middleware('auth');

	// API Entity Routes
	Route::group(['prefix' => LaravelLocalization::setLocale(), 'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath']], function () {

		Route::group(['prefix' => 'api', 'middleware' => 'auth:api'], function () {

			$entities = Entity::where('cgroup', 'entity')->get();
			foreach ($entities as $entity) {

				$apkey = $entity->resource_slug;

				$controllerClass = 'Lara\\App\\Http\\Controllers\\Front\\Api\\' . $entity->controller;
				if (class_exists($controllerClass)) {
					Route::resource($apkey, 'Front\\Api\\' . $entity->controller, ['as' => 'api', 'parameters' => [$apkey => 'id']])->only(['index', 'show']);
				}

			}

		});

	});

	// FRONT Entity Routes
	Route::group(['prefix' => LaravelLocalization::setLocale(), 'middleware' => ['web', 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'dateLocale']], function () use ($laraControllerAction, $laraMenuItemIsRoutable, $laraRouteMiddleware) {

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

			// Home
			Route::get('/', 'Front\Page\HomeController@show')
				->name('special.home.show')
				->middleware($laraRouteMiddleware($rootMenuItem->entity, $rootMenuItem));

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

			if (!$laraMenuItemIsRoutable($menuPage)) {
				continue;
			}

			$action = $laraControllerAction('Front\Page', $menuPage->entity->controller, $menuPage->entityview->method);

			if ($action === null) {
				continue;
			}

			Route::get($menuPage->route, $action)
				->name($menuPage->routename)
				->middleware($laraRouteMiddleware($menuPage->entity, $menuPage));

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

			if (!$laraMenuItemIsRoutable($menuItem)) {
				continue;
			}

			// entities and forms only
			if (!in_array($menuItem->entity->cgroup, ['entity', 'form'])) {
				continue;
			}

			$listAction = $laraControllerAction('Front\Entity', $menuItem->entity->controller, $menuItem->entityview->method);
			$showAction = $laraControllerAction('Front\Entity', $menuItem->entity->controller, 'show', true);

			if ($listAction === null) {
				continue;
			}

			$menuItemMiddleware = $laraRouteMiddleware($menuItem->entity, $menuItem);

			if ($menuItem->entity->objrel_has_terms == 1) {

				if (empty($menuItem->tag_id)) {

					// SEO Routes for tags
					Route::get($menuItem->route, $listAction)
						->name($menuItem->routename)->middleware($menuItemMiddleware);

					// add .html to object slug, so we can distinguish between an object slug and a (sub)cat slug.
					if ($showAction !== null) {
						Route::get($menuItem->route . '/{slug}.html', $showAction)
							->name($menuItem->routename . '.show')->middleware($menuItemMiddleware);
					}

					$tags = Tag::resourceIs($menuItem->entity->resource_slug)->whereNotNull('route')->get();

					foreach ($tags as $tag) {

						$tagslug = str_replace('.', '/', $tag->route);
						$tagRouteName = $entity_tag_prefix . '.' . $menuItem->entity->resource_slug . '.' . $menuItem->id . '.' . $tag->route . '.' . $menuItem->entityview->method;

						Route::get($menuItem->route . '/' . $tagslug, $listAction)
							->name($tagRouteName)->middleware($menuItemMiddleware);

						if ($showAction !== null) {
							Route::get($menuItem->route . '/' . $tagslug . '/{slug}.html', $showAction)
								->name($tagRouteName . '.show')->middleware($menuItemMiddleware);
						}

					}

				} else {

					// entity with a tag (no show method, no tags)
					Route::get($menuItem->route, $listAction)
						->name($menuItem->routename)->middleware($menuItemMiddleware);

				}

			} else {

				Route::get($menuItem->route, $listAction)
					->name($menuItem->routename)->middleware($menuItemMiddleware);

				if ($showAction !== null) {
					Route::get($menuItem->route . '/{slug}', $showAction)
						->name($menuItem->routename . '.show')->middleware($menuItemMiddleware);
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

			if (!$laraMenuItemIsRoutable($menuForm)) {
				continue;
			}

			// exclude CUSTOM entities
			if ($menuForm->entity->cgroup == 'entity') {
				continue;
			}

			$formAction = $laraControllerAction('Front\Form', $menuForm->entity->controller, $menuForm->entityview->method);
			$processAction = $laraControllerAction('Front\Form', $menuForm->entity->controller, 'process');

			if ($formAction === null) {
				continue;
			}

			Route::get($menuForm->route, $formAction)
				->name($menuForm->routename)
				->middleware($laraRouteMiddleware($menuForm->entity, $menuForm));

			// create route for regular POST without AJAX
			if ($processAction !== null) {
				Route::post($menuForm->route, $processAction)
					->name('form.' . $menuForm->entity->resource_slug . '.' . $menuForm->id . '.process')
					->middleware([ProtectAgainstSpam::class, 'throttle:10,86400']); // patch 6.2.23
			}

		}

		Route::group(['prefix' => 'ajax'], function () use ($menuForms, $laraControllerAction, $laraMenuItemIsRoutable) {

			foreach ($menuForms as $menuForm) {

				if (!$laraMenuItemIsRoutable($menuForm, false)) {
					continue;
				}

				// exclude CUSTOM entities
				if ($menuForm->entity->cgroup == 'entity') {
					continue;
				}

				$redirectAction = $laraControllerAction('Front\Form', $menuForm->entity->controller, 'redirect', true);
				$processAction = $laraControllerAction('Front\Form', $menuForm->entity->controller, 'process', true);

				// Add AJAX route
				if ($redirectAction !== null) {
					Route::get($menuForm->entity->resource_slug, $redirectAction)
						->name('ajax.' . $menuForm->entity->resource_slug . '.redirect');
				}

				if ($processAction !== null) {
					Route::post($menuForm->entity->resource_slug, $processAction)
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
		Route::group(['prefix' => 'content'], function () use ($laraControllerAction, $laraRouteMiddleware) {

			// These prefixes are used for the route NAMES, and NOT the URI path
			$content_prefix = 'content';
			$content_tag_prefix = 'contenttag';

			// Page Routes
			$entities = Entity::where('cgroup', 'page')->get();
			foreach ($entities as $entity) {

				$showAction = $laraControllerAction('Front\Page', $entity->controller, 'show');

				if ($showAction === null) {
					continue;
				}

				Route::get($entity->resource_slug . '/{id}', $showAction)
					->name($content_prefix . '.' . $entity->resource_slug . '.show')
					->middleware($laraRouteMiddleware($entity, null, false));

			}

			// Entity Routes
			$entities = Entity::where('cgroup', 'entity')->get();

			foreach ($entities as $entity) {

				$indexAction = $laraControllerAction('Front\Entity', $entity->controller, 'index');
				$showAction = $laraControllerAction('Front\Entity', $entity->controller, 'show', true);

				if ($indexAction === null) {
					continue;
				}

				$entityMiddleware = $laraRouteMiddleware($entity, null, false);

				if ($entity->objrel_has_terms == 1) {

					Route::get($entity->resource_slug, $indexAction)
						->name($content_tag_prefix . '.' . $entity->resource_slug . '.index')->middleware($entityMiddleware);

					// add .html to slug, so we can distinguish between an object slug and a (sub)cat slug.
					if ($showAction !== null) {
						Route::get($entity->resource_slug . '/{id}.html', $showAction)
							->name($content_tag_prefix . '.' . $entity->resource_slug . '.index.show')->middleware($entityMiddleware);
					}

					$tags = Tag::resourceIs($entity->resource_slug)->whereNotNull('route')->get();

					foreach ($tags as $tag) {

						$tagroutename = str_replace('/', '.', $tag->route);

						Route::get($entity->resource_slug . '/' . $tag->route, $indexAction)
							->name($content_tag_prefix . '.' . $entity->resource_slug . '.' . $tagroutename . '.index')->middleware($entityMiddleware);

						if ($showAction !== null) {
							Route::get($entity->resource_slug . '/' . $tag->route . '/{id}.html', $showAction)
								->name($content_tag_prefix . '.' . $entity->resource_slug . '.' . $tagroutename . '.index.show')->middleware($entityMiddleware);
						}

					}

				} else {

					Route::get($entity->resource_slug, $indexAction)
						->name($content_prefix . '.' . $entity->resource_slug . '.index')->middleware($entityMiddleware);

					if ($showAction !== null) {
						Route::get($entity->resource_slug . '/{id}', $showAction)
							->name($content_prefix . '.' . $entity->resource_slug . '.index.show')->middleware($entityMiddleware);
					}

				}

			}

		});

		// 404
		Route::get('/{any}', '\Lara\App\Http\Controllers\Front\Error\AppErrorController@show')->where('any', '.*')->name('error.show.404');

	});

}



