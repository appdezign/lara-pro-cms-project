<?php

namespace Lara\App\Http\Controllers\Front\Entity;

use Lara\App\Http\Controllers\Front\Base\AppFrontController;

use Lara\App\Models\Video;

class VideosController extends AppFrontController
{

	protected function make(): Video
	{
		return Video::create();
	}

}
