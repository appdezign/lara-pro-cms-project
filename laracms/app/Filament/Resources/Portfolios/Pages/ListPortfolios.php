<?php

namespace Lara\App\Filament\Resources\Portfolios\Pages;

use Lara\Admin\Pages\Lara\LaraListRecords;
use Lara\App\Filament\Resources\Portfolios\PortfolioResource;

class ListPortfolios extends LaraListRecords
{
    protected static string $resource = PortfolioResource::class;
}
