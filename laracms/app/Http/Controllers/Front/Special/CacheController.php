<?php

namespace Lara\App\Http\Controllers\Front\Special;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Lara\Admin\Traits\HasCache;

class CacheController extends Controller
{

	use HasCache;

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

		static::clearCacheTypes();

		if(!empty($this->redirectSlug)) {
			return redirect()->route($this->redirectRoute, ['slug' => $this->redirectSlug]);
		} else {
			return redirect()->route($this->redirectRoute);
		}

	}
}
