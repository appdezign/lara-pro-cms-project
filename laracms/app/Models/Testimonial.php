<?php

namespace Lara\App\Models;

use Lara\App\Database\Factories\TestimonialFactory;
use Lara\Common\Models\BaseModel;
use Lara\Common\Http\Concerns\HasLanguage;

class Testimonial extends BaseModel
{
	use HasLanguage;

	protected $table = 'lara_content_testimonials';

	protected static function newFactory()
	{
		return TestimonialFactory::new();
	}
}
