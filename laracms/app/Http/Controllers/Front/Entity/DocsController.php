<?php

namespace Lara\App\Http\Controllers\Front\Entity;

use Lara\App\Http\Controllers\Front\Base\AppFrontController;

use Lara\App\Models\Doc;

class DocsController extends AppFrontController
{

	protected function make(): Doc
	{
		return Doc::create();
	}

}
