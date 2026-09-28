<?php

namespace Lara\App\Filament\Resources\Testimonials\Pages;

use Lara\Admin\Pages\Lara\LaraReorderRecords;
use Lara\App\Filament\Resources\Testimonials\TestimonialResource;

class ReorderTestimonials extends LaraReorderRecords
{
    protected static string $resource = TestimonialResource::class;
}
