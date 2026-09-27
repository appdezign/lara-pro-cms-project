<?php

namespace Lara\App\Entities;

use Lara\Common\Entities\LaraEntity;

class BlogsEntity extends LaraEntity
{
    public ?string $resource_slug = 'blogs';

    protected ?string $module = 'lara-app';
}
