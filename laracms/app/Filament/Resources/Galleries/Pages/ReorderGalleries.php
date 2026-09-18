<?php

namespace Lara\App\Filament\Resources\Galleries\Pages;

use Lara\Admin\Pages\Lara\LaraReorderRecords;
use Lara\App\Filament\Resources\Galleries\GalleryResource;

class ReorderGalleries extends LaraReorderRecords
{
    protected static string $resource = GalleryResource::class;
}
