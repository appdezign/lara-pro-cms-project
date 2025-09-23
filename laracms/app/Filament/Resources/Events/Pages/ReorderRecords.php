<?php

namespace Lara\App\Filament\Resources\Events\Pages;

use Lara\Admin\Pages\Lara\LaraReorderRecords;
use Lara\App\Filament\Resources\Events\EventResource;

class ReorderRecords extends LaraReorderRecords
{
    protected static string $resource = EventResource::class;
}
