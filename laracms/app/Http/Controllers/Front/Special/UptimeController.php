<?php

namespace Lara\App\Http\Controllers\Front\Special;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;


class UptimeController extends Controller
{

	protected $data;

	public function __construct()
	{

		$app = app();
		$this->data = $app->make('stdClass');

	}


	public function show(Request $request) {

		$this->data->domain = config('app.url');

		return view('_uptime.show',[
			'data' => $this->data,
		]);

	}
}
