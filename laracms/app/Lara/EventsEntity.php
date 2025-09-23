<?php

namespace Lara\App\Lara;

use Lara\Common\Lara\LaraEntity;

class EventsEntity extends LaraEntity
{
	public ?string $resource_slug = 'events';
	protected ?string $module = 'lara-app';
}
