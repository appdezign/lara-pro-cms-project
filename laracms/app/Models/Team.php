<?php

namespace Lara\App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lara\Common\Models\BaseModel;

class Team extends BaseModel
{
    protected $table = 'lara_content_teams';

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
	 * @return BelongsTo
	 */
	public function location(): BelongsTo
	{
		return $this->belongsTo('Lara\App\Models\Location', 'location_id');
	}

}
