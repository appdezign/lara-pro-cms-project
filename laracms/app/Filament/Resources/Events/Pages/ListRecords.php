<?php

namespace Lara\App\Filament\Resources\Events\Pages;

use Lara\Admin\Pages\Lara\LaraListRecords;
use Lara\App\Filament\Resources\Events\EventResource;

class ListRecords extends LaraListRecords
{
    protected static string $resource = EventResource::class;
}
