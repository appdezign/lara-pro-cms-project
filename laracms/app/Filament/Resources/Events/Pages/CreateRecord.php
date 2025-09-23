<?php

namespace Lara\App\Filament\Resources\Events\Pages;

use Lara\Admin\Pages\Lara\LaraCreateRecord;
use Lara\App\Filament\Resources\Events\EventResource;

class CreateRecord extends LaraCreateRecord
{
    protected static string $resource = EventResource::class;
}
