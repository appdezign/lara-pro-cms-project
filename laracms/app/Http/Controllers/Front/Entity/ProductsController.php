<?php

namespace Lara\App\Http\Controllers\Front\Entity;

use Lara\App\Http\Controllers\Front\Base\AppFrontController;

use Lara\App\Models\Product;

class ProductsController extends AppFrontController
{

	protected function make(): Product
	{
		return Product::create();
	}

}
