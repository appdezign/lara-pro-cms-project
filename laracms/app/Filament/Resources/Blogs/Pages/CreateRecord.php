<?php

namespace Lara\App\Filament\Resources\Blogs\Pages;

use Lara\Admin\Pages\Lara\LaraCreateRecord;
use Lara\App\Filament\Resources\Blogs\BlogResource;

class CreateRecord extends LaraCreateRecord
{
    protected static string $resource = BlogResource::class;
}
