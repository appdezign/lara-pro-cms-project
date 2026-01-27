<?php

namespace Lara\App\Models;

use Lara\Common\Models\BaseModel;
use Lara\Common\Http\Concerns\HasLanguage;

class Blog extends BaseModel
{
	use HasLanguage;

	protected $table = 'lara_content_blogs';

}
