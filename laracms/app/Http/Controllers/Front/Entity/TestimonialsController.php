<?php

namespace Lara\App\Http\Controllers\Front\Entity;

use Lara\App\Http\Controllers\Front\Base\AppFrontController;

use Lara\App\Models\Testimonial;

class TestimonialsController extends AppFrontController
{

	protected function make(): Testimonial
	{
		return Testimonial::create();
	}

}
