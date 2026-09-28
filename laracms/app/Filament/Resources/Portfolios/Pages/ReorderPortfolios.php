<?php

namespace Lara\App\Filament\Resources\Portfolios\Pages;

use Lara\Admin\Pages\Lara\LaraReorderRecords;
use Lara\App\Filament\Resources\Portfolios\PortfolioResource;

class ReorderPortfolios extends LaraReorderRecords
{
    protected static string $resource = PortfolioResource::class;
}
