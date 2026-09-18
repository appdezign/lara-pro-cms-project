<?php

namespace Lara\App\Providers;

use Lara\Common\Providers\LaraModuleRouteProvider;

class RouteServiceProvider extends LaraModuleRouteProvider
{

	/**
	 * @var string
	 */
	protected $namespace = 'Lara\App\Http\Controllers';

	protected function routesPath(): string
	{
		return __DIR__ . '/../Routes';
	}

}
