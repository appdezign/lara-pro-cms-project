<?php

namespace Lara\App\Http\Controllers\Front\Entity;

use Lara\App\Http\Controllers\Front\Base\AppFrontController;

use Lara\App\Models\Event;

class EventsController extends AppFrontController
{

	protected function make(): Event
	{
		return Event::create();
	}

}
