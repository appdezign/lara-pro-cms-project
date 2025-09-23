<?php

namespace Lara\App\Http\Controllers\Front\Special;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Artisan;


class CacheController extends Controller
{

	protected $redirectRoute;
	protected $redirectSlug;

	public function __construct()
	{

	}


	public function process(Request $request) {

		if($request->has('redirect')) {

			$this->redirectRoute = $request->get('redirect');
			if(!empty($request->get('slug'))) {
				$this->redirectSlug = $request->get('slug');
			} else {
				$this->redirectSlug = null;
			}

		} else {
			$this->redirectRoute = 'special.home.show';
			$this->redirectSlug = null;
		}

		Artisan::call('cache:clear');
		Artisan::call('config:clear');
		Artisan::call('view:clear');
		Artisan::call('httpcache:clear');
		$request->session()->put('routecacheclear', true);

		if(!empty($this->redirectSlug)) {
			return redirect()->route($this->redirectRoute, ['slug' => $this->redirectSlug]);
		} else {
			return redirect()->route($this->redirectRoute);
		}

	}
}
