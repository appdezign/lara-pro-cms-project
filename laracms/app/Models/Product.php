<?php

namespace Lara\App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lara\Common\Models\BaseModel;

class Product extends BaseModel
{
    protected $table = 'lara_content_products';

	protected array $appendCasts = [
		'myradio' => 'array',
		'mycheckboxlist' => 'array',
		'mymultiselect' => 'array',
		'mymultitogglebuttons' => 'array',
		'mytagsinput' => 'array',
	];

	public function __construct($attributes = [])
	{
		$this->mergeCasts($this->appendCasts);
		parent::__construct($attributes);
	}

    public function languageParent(): BelongsTo
	{
		return $this->belongsTo(self::class, 'language_parent');
	}

	public function languageChildren(): HasMany
	{
		return $this->hasMany(self::class, 'language_parent');
	}
}
