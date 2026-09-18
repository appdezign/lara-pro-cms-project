<?php

namespace Lara\App\Entities;

use Lara\Common\Entities\LaraEntity;

class ServicesEntity extends LaraEntity
{
	public ?string $resource_slug = 'services';
	protected ?string $module = 'lara-app';
}
