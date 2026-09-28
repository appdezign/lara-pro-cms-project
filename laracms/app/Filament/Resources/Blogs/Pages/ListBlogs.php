<?php

namespace Lara\App\Filament\Resources\Blogs\Pages;

use Lara\Admin\Pages\Lara\LaraListRecords;
use Lara\App\Filament\Resources\Blogs\BlogResource;

class ListBlogs extends LaraListRecords
{
    protected static string $resource = BlogResource::class;
}
