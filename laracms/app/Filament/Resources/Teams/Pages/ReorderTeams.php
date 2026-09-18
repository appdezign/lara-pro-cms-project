<?php

namespace Lara\App\Filament\Resources\Teams\Pages;

use Lara\Admin\Pages\Lara\LaraReorderRecords;
use Lara\App\Filament\Resources\Teams\TeamResource;

class ReorderTeams extends LaraReorderRecords
{
    protected static string $resource = TeamResource::class;
}
