<?php

namespace Lara\App\Models;

use Lara\App\Database\Factories\PortfolioFactory;
use Lara\Common\Http\Concerns\HasLanguage;
use Lara\Common\Models\BaseModel;

class Portfolio extends BaseModel
{
    use HasLanguage;

    protected $table = 'lara_content_portfolios';

    protected static function newFactory()
    {
        return PortfolioFactory::new();
    }
}
