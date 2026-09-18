<?php

namespace Lara\App\Entities;

use Lara\Common\Entities\LaraEntity;

class VideosEntity extends LaraEntity
{
	public ?string $resource_slug = 'videos';
	protected ?string $module = 'lara-app';
}
