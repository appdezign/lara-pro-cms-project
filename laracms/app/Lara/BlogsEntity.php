<?php

namespace Lara\App\Lara;

use Lara\Common\Lara\LaraEntity;

class BlogsEntity extends LaraEntity
{
	public ?string $resource_slug = 'blogs';
	protected ?string $module = 'lara-app';
}
