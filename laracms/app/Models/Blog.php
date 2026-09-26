<?php

namespace Lara\App\Models;

use Lara\App\Database\Factories\BlogFactory;
use Lara\Common\Models\BaseModel;
use Lara\Common\Http\Concerns\HasLanguage;

class Blog extends BaseModel
{
	use HasLanguage;

	protected $table = 'lara_content_blogs';

	protected static function newFactory()
	{
		return BlogFactory::new();
	}

}
