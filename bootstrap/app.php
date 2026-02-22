<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
	->registered(function ($app) {
		// Set public path on Plesk Server
		// $app->usePublicPath(path: realpath(base_path('../httpdocs')));

		// Set public path on Servebolt Server
		// $app->usePublicPath(path: realpath(base_path('../public')));
	})
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
		//
    })
	->withCommands([
		// make sure we can use migrate:generate from controller
		KitLoong\MigrationsGenerator\MigrateGenerateCommand::class,
	])
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
