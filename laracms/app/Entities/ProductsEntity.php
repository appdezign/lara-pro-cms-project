<?php

namespace Lara\App\Entities;

use Lara\Common\Entities\LaraEntity;

class ProductsEntity extends LaraEntity
{
	public ?string $resource_slug = 'products';
	protected ?string $module = 'lara-app';
}
