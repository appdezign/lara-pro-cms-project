<?php

namespace Lara\App\Http\Controllers\Front\Api;

use Lara\Front\Http\Controllers\Api\Base\BaseApiController;

use Lara\App\Models\Blog;

class BlogsController extends BaseApiController
{

	protected function make(): Blog {
		return Blog::create();
	}

}
