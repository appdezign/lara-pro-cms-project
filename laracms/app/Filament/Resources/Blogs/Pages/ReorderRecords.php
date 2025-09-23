<?php

namespace Lara\App\Filament\Resources\Blogs\Pages;

use Lara\Admin\Pages\Lara\LaraReorderRecords;
use Lara\App\Filament\Resources\Blogs\BlogResource;

class ReorderRecords extends LaraReorderRecords
{
    protected static string $resource = BlogResource::class;
}
