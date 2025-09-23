<?php

namespace Lara\App\Lara;

use Lara\Common\Lara\LaraEntity;

class DocsEntity extends LaraEntity
{
	public ?string $resource_slug = 'docs';
	protected ?string $module = 'lara-app';
}
