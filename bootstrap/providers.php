<?php

use App\Providers\AppServiceProvider;
use Awcodes\Mason\MasonServiceProvider;
use Awcodes\RicherEditor\RicherEditorServiceProvider;
use Lara\Admin\Providers\AdminPanelProvider;
use Lara\Admin\Providers\LaraAdminServiceProvider;
use Lara\App\Providers\LaraAppServiceProvider;
use Lara\App\Providers\RouteServiceProvider;
use Lara\Common\Providers\LaraCommonRouteProvider;
use Lara\Common\Providers\LaraCommonServiceProvider;
use Lara\Front\Providers\LaraFrontRouteProvider;
use Lara\Front\Providers\LaraFrontServiceProvider;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthServiceProvider;

return [

    // App
    AppServiceProvider::class,

    // Filament
    AdminPanelProvider::class,

    // Service providers
    LaraAdminServiceProvider::class,
    LaraCommonServiceProvider::class,
    LaraFrontServiceProvider::class,
    LaraAppServiceProvider::class,

    // Route providers
    LaraCommonRouteProvider::class,
    LaraFrontRouteProvider::class,
    RouteServiceProvider::class,

    RicherEditorServiceProvider::class,
    MasonServiceProvider::class,
    FilamentSpatieLaravelHealthServiceProvider::class,

];
