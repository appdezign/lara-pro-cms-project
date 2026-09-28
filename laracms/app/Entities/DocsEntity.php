<?php

namespace Lara\App\Entities;

use Lara\Common\Entities\LaraEntity;

class DocsEntity extends LaraEntity
{
    public ?string $resource_slug = 'docs';

    protected ?string $module = 'lara-app';
}
