<?php

namespace Lara\App\Entities;

use Lara\Common\Entities\LaraEntity;

class LocationsEntity extends LaraEntity
{
	public ?string $resource_slug = 'locations';
	protected ?string $module = 'lara-app';
}
