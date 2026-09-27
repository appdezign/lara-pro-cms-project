<?php

namespace Lara\App\Models;

use Lara\App\Database\Factories\DocFactory;
use Lara\Common\Http\Concerns\HasLanguage;
use Lara\Common\Models\BaseModel;

class Doc extends BaseModel
{
    use HasLanguage;

    protected $table = 'lara_content_docs';

    protected static function newFactory()
    {
        return DocFactory::new();
    }
}
