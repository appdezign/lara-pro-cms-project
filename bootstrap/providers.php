<?php

return [

	// App
    App\Providers\AppServiceProvider::class,

	// Filament
    Lara\Admin\Providers\AdminPanelProvider::class,

	// Service providers
    Lara\Admin\Providers\LaraAdminServiceProvider::class,
	Lara\Common\Providers\LaraCommonServiceProvider::class,
	Lara\Front\Providers\LaraFrontServiceProvider::class,
	Lara\App\Providers\LaraAppServiceProvider::class,

	// Route providers
    Lara\Common\Providers\LaraCommonRouteProvider::class,
	Lara\Front\Providers\LaraFrontRouteProvider::class,
	Lara\App\Providers\RouteServiceProvider::class,

	Barryvdh\HttpCache\ServiceProvider::class,
	Awcodes\RicherEditor\RicherEditorServiceProvider::class,
	Awcodes\Mason\MasonServiceProvider::class,
	ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthServiceProvider::class,

];
