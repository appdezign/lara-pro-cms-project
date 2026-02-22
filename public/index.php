<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Custom maintenance mode...
if (file_exists($down = __DIR__.'/../laravel/storage/framework/down')) {
	$data = json_decode(file_get_contents($down), true);
	$defaultOutput = '<html><body><h1>maintenance</h1></div></body></html>';
	$output = (isset($data['template'])) ? $data['template'] : $defaultOutput;
	echo $output;
	exit();
}

if(PHP_OS == 'Linux') {

	// plesk
	require __DIR__ . '/../laravel/vendor/autoload.php';

	(require_once __DIR__ . '/../laravel/bootstrap/app.php')
		->handleRequest(Request::capture());

} else {

	// local
	require __DIR__ . '/../vendor/autoload.php';

	(require_once __DIR__ . '/../bootstrap/app.php')
		->handleRequest(Request::capture());
}


