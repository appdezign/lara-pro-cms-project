<?php

namespace Lara\App\Models;

use Lara\App\Database\Factories\VideoFactory;
use Lara\Common\Models\BaseModel;
use Lara\Common\Http\Concerns\HasLanguage;

class Video extends BaseModel
{
	use HasLanguage;

	protected $table = 'lara_content_videos';

	protected static function newFactory()
	{
		return VideoFactory::new();
	}

}
