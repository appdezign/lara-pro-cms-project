<?php

namespace Lara\App\Http\Controllers\Front\Entity;

use Lara\App\Http\Controllers\Front\Base\AppFrontController;

use Lara\App\Models\Location;

class LocationsController extends AppFrontController
{

	public function __construct()
	{
		parent::__construct();
	}

	protected function make(): Location
	{
		return Location::create();
	}

}
