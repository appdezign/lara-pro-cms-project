<?php

namespace Lara\App\Entities;

use Lara\Common\Entities\LaraEntity;

class ContactformsEntity extends LaraEntity
{
	public ?string $resource_slug = 'contactforms';
	protected ?string $module = 'lara-app';
}
