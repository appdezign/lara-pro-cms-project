<?php

namespace Lara\App\Models;

use Lara\App\Database\Factories\BlogFactory;
use Lara\Common\Http\Concerns\HasLanguage;
use Lara\Common\Models\BaseModel;

class Blog extends BaseModel
{
    use HasLanguage;

    protected $table = 'lara_content_blogs';

    protected static function newFactory()
    {
        return BlogFactory::new();
    }
}
