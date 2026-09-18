<?php

namespace Lara\App\Http\Controllers\Front\Special;

use App\Http\Controllers\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use Lara\Admin\Concerns\HasCache;

class CacheController extends Controller
{

	use HasCache;

	private const DEFAULT_REDIRECT_ROUTE = 'special.home.show';

	protected ?string $redirectRoute = null;

	protected ?string $redirectSlug = null;

	public function process(Request $request): RedirectResponse
	{

		$requestedRoute = $request->string('redirect')->toString();

		// only honour a route name that actually exists, so a bad or crafted
		// query string cannot turn this into an unhandled exception
		$this->redirectRoute = Route::has($requestedRoute)
			? $requestedRoute
			: self::DEFAULT_REDIRECT_ROUTE;

		$slug = $request->string('slug')->toString();
		$this->redirectSlug = $slug !== '' ? $slug : null;

		static::clearCacheTypes();

		if (!empty($this->redirectSlug)) {
			return redirect()->route($this->redirectRoute, ['slug' => $this->redirectSlug]);
		}

		return redirect()->route($this->redirectRoute);

	}
}
