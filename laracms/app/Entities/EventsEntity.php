<?php

namespace Lara\App\Entities;

use Lara\Common\Entities\LaraEntity;

class EventsEntity extends LaraEntity
{
    public ?string $resource_slug = 'events';

    protected ?string $module = 'lara-app';
}
