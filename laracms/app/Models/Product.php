<?php

namespace Lara\App\Models;

use Lara\Common\Models\BaseModel;
use Lara\Common\Http\Concerns\HasLanguage;

class Product extends BaseModel
{
	use HasLanguage;

	protected $table = 'lara_content_products';

	protected function casts(): array
	{
		return array_merge(parent::casts(), [
			'myradio'              => 'array',
			'mycheckboxlist'       => 'array',
			'mymultiselect'        => 'array',
			'mymultitogglebuttons' => 'array',
			'mytagsinput'          => 'array',
		]);
	}

}
