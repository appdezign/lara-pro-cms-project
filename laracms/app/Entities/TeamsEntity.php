<?php

namespace Lara\App\Entities;

use Lara\Common\Entities\LaraEntity;

class TeamsEntity extends LaraEntity
{
    public ?string $resource_slug = 'teams';

    protected ?string $module = 'lara-app';
}
