<?php

namespace Lara\App\Http\Controllers\Front\Entity;

use Lara\App\Http\Controllers\Front\Base\AppFrontController;

use Lara\App\Models\Portfolio;

class PortfoliosController extends AppFrontController
{

	//

	protected function make(): Portfolio
	{
		return Portfolio::create();
	}

}
