<?php

namespace Lara\App\Models;

use Lara\App\Database\Factories\ProductFactory;
use Lara\Common\Http\Concerns\HasLanguage;
use Lara\Common\Models\BaseModel;

class Product extends BaseModel
{
    use HasLanguage;

    protected $table = 'lara_content_products';

    protected static function newFactory()
    {
        return ProductFactory::new();
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'myradio' => 'array',
            'mycheckboxlist' => 'array',
            'mymultiselect' => 'array',
            'mymultitogglebuttons' => 'array',
            'mytagsinput' => 'array',
        ]);
    }
}
