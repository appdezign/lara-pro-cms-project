<?php

namespace Lara\App\Models;

use Lara\Common\Models\BaseModel;
use Lara\Common\Http\Concerns\HasLanguage;

class Video extends BaseModel
{
	use HasLanguage;

	protected $table = 'lara_content_videos';

}
