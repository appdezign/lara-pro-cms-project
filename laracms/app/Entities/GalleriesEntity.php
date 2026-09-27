<?php

namespace Lara\App\Entities;

use Lara\Common\Entities\LaraEntity;

class GalleriesEntity extends LaraEntity
{
    public ?string $resource_slug = 'galleries';

    protected ?string $module = 'lara-app';
}
