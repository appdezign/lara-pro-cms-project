<?php

namespace Lara\App\Models;

use Lara\App\Database\Factories\ServiceFactory;
use Lara\Common\Http\Concerns\HasLanguage;
use Lara\Common\Models\BaseModel;

class Service extends BaseModel
{
    use HasLanguage;

    protected $table = 'lara_content_services';

    protected static function newFactory()
    {
        return ServiceFactory::new();
    }
}
