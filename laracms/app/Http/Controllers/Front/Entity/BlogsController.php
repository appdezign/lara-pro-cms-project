<?php

namespace Lara\App\Http\Controllers\Front\Entity;

use Lara\App\Http\Controllers\Front\Base\AppFrontController;

use Lara\App\Models\Blog;

class BlogsController extends AppFrontController
{

	protected function make(): Blog
	{
		return Blog::create();
	}

}
