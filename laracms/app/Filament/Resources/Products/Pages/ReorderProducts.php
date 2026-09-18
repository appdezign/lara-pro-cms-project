<?php

namespace Lara\App\Filament\Resources\Products\Pages;

use Lara\Admin\Pages\Lara\LaraReorderRecords;
use Lara\App\Filament\Resources\Products\ProductResource;

class ReorderProducts extends LaraReorderRecords
{
    protected static string $resource = ProductResource::class;
}
