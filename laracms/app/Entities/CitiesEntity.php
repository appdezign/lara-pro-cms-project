<?php

namespace Lara\App\Entities;

use Lara\Common\Entities\LaraEntity;

class CitiesEntity extends LaraEntity
{
	public ?string $resource_slug = 'cities';
	protected ?string $module = 'lara-app';
}
