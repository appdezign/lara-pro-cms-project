<?php

namespace Lara\App\Filament\Resources\Products\Pages;

use Lara\Admin\Pages\Lara\LaraListRecords;
use Lara\App\Filament\Resources\Products\ProductResource;

class ListProducts extends LaraListRecords
{
    protected static string $resource = ProductResource::class;
}
