<?php

namespace Lara\App\Models;

use Lara\App\Database\Factories\GalleryFactory;
use Lara\Common\Models\BaseModel;
use Lara\Common\Http\Concerns\HasLanguage;

class Gallery extends BaseModel
{
	use HasLanguage;

	protected $table = 'lara_content_galleries';

	protected static function newFactory()
	{
		return GalleryFactory::new();
	}

}
