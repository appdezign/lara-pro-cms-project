<?php

namespace Lara\App\Http\Controllers\Front\Entity;

use Lara\App\Http\Controllers\Front\Base\AppFrontController;

use Lara\App\Models\Event;

class EventsController extends AppFrontController
{

	public function __construct()
	{
		parent::__construct();
	}

	protected function make(): Event
	{
		return Event::create();
	}

}
