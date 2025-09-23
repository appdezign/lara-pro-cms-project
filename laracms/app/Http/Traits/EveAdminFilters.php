<?php

namespace Lara\App\Http\Traits;

use Illuminate\Support\Facades\Input;

trait EveAdminFilters
{
	private function customAdminEntityFilter($request, $entity, $collection)
	{

		// Add custom filters here ...

		return $collection;

	}

}
