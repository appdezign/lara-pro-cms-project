<?php

namespace Lara\App\Http\Controllers\Front\Entity;

use Lara\App\Http\Controllers\Front\Base\AppFrontController;

use Lara\App\Models\Gallery;

class GalleriesController extends AppFrontController
{

	public function __construct()
	{
		parent::__construct();
	}

	protected function make(): Gallery
	{
		return Gallery::create();
	}

}
