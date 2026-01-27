<?php

namespace Lara\App\Http\Controllers\Front\Entity;

use Lara\App\Http\Controllers\Front\Base\AppFrontController;

use Lara\App\Models\Service;

class ServicesController extends AppFrontController
{

	protected function make(): Service
	{
		return Service::create();
	}

}
