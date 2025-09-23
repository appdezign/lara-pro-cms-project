<?php

namespace Lara\App\Filament\Resources\Events\Pages;

use Lara\Admin\Pages\Lara\LaraEditRecord;
use Lara\App\Filament\Resources\Events\EventResource;

class EditRecord extends LaraEditRecord
{
    protected static string $resource = EventResource::class;
}
