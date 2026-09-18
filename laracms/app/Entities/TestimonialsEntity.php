<?php

namespace Lara\App\Entities;

use Lara\Common\Entities\LaraEntity;

class TestimonialsEntity extends LaraEntity
{
	public ?string $resource_slug = 'testimonials';
	protected ?string $module = 'lara-app';
}
