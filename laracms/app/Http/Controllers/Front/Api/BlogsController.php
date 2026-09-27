<?php

namespace Lara\App\Http\Controllers\Front\Api;

use Lara\App\Models\Blog;
use Lara\Front\Http\Controllers\Api\Base\BaseApiController;

class BlogsController extends BaseApiController
{
    protected function make(): Blog
    {
        return Blog::create();
    }
}
