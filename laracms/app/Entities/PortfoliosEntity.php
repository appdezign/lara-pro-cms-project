<?php

namespace Lara\App\Entities;

use Lara\Common\Entities\LaraEntity;

class PortfoliosEntity extends LaraEntity
{
    public ?string $resource_slug = 'portfolios';

    protected ?string $module = 'lara-app';
}
