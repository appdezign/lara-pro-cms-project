<?php

namespace Lara\App\Lara;

use Lara\Common\Lara\LaraEntity;

class ProductsEntity extends LaraEntity
{
	public ?string $resource_slug = 'products';
	protected ?string $module = 'lara-app';
}
