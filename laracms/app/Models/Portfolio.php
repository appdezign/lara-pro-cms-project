<?php

namespace Lara\App\Models;

use Lara\Common\Models\BaseModel;
use Lara\Common\Http\Concerns\HasLanguage;

class Portfolio extends BaseModel
{
	use HasLanguage;

	protected $table = 'lara_content_portfolios';

}
