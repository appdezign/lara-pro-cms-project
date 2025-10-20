<?php

namespace Lara\App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lara\Common\Models\BaseModel;

class Location extends BaseModel
{
    protected $table = 'lara_content_locations';

	protected array $appendCasts = [];

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

	public function teams(): HasMany
	{
		return $this->hasMany('Lara\App\Models\Team', 'location_id');
	}

	public function events(): HasMany
	{
		return $this->hasMany('Lara\App\Models\Event', 'location_id');
	}

}
