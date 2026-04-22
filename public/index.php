<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Custom maintenance mode...
if (file_exists($down = __DIR__.'/../storage/framework/down')) {
	$data = json_decode(file_get_contents($down), true);
	$defaultOutput = '<html><body><h1>maintenance</h1></div></body></html>';
	$output = (isset($data['template'])) ? $data['template'] : $defaultOutput;
	echo $output;
	exit();
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());


