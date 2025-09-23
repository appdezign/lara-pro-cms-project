<?php

namespace Lara\App\Filament\Resources\Videos\Pages;

use Lara\Admin\Pages\Lara\LaraReorderRecords;
use Lara\App\Filament\Resources\Videos\VideoResource;

class ReorderRecords extends LaraReorderRecords
{
    protected static string $resource = VideoResource::class;
}
