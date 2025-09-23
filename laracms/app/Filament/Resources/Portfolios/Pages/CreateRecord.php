<?php

namespace Lara\App\Filament\Resources\Portfolios\Pages;

use Lara\Admin\Pages\Lara\LaraCreateRecord;
use Lara\App\Filament\Resources\Portfolios\PortfolioResource;

class CreateRecord extends LaraCreateRecord
{
    protected static string $resource = PortfolioResource::class;
}
