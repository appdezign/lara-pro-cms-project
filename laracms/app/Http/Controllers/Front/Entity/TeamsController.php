<?php

namespace Lara\App\Http\Controllers\Front\Entity;

use Lara\App\Http\Controllers\Front\Base\AppFrontController;

use Lara\App\Models\Team;

class TeamsController extends AppFrontController
{

	public function __construct()
	{
		parent::__construct();
	}

	protected function make(): Team
	{
		return Team::create();
	}

}
