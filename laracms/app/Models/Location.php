<?php

namespace Lara\App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lara\Common\Models\BaseModel;

class Location extends BaseModel
{
    protected $table = 'lara_content_locations';

	/**
	 * @return BelongsTo
	 */
	public function languageParent(): BelongsTo
	{
		return $this->belongsTo(self::class, 'language_parent');
	}

	/**
	 * @return HasMany
	 */
	public function languageChildren(): HasMany
	{
		return $this->hasMany(self::class, 'language_parent');
	}

	/**
	 * @return HasMany
	 */
	public function teams(): HasMany
	{
		return $this->hasMany('Lara\App\Models\Team', 'location_id');
	}

	/**
	 * @return HasMany
	 */
	public function events(): HasMany
	{
		return $this->hasMany('Lara\App\Models\Event', 'location_id');
	}

}
