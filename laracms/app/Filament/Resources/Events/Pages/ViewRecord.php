<?php

namespace Lara\App\Filament\Resources\Events\Pages;

use Lara\Admin\Pages\Lara\LaraViewRecord;
use Lara\App\Filament\Resources\Events\EventResource;

class ViewRecord extends LaraViewRecord
{
    protected static string $resource = EventResource::class;
}
