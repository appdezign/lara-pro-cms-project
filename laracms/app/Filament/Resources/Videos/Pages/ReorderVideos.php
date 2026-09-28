<?php

namespace Lara\App\Filament\Resources\Videos\Pages;

use Lara\Admin\Pages\Lara\LaraReorderRecords;
use Lara\App\Filament\Resources\Videos\VideoResource;

class ReorderVideos extends LaraReorderRecords
{
    protected static string $resource = VideoResource::class;
}
