<?php

namespace Lara\App\Models;

use Lara\App\Database\Factories\VideoFactory;
use Lara\Common\Http\Concerns\HasLanguage;
use Lara\Common\Models\BaseModel;

class Video extends BaseModel
{
    use HasLanguage;

    protected $table = 'lara_content_videos';

    protected static function newFactory()
    {
        return VideoFactory::new();
    }
}
