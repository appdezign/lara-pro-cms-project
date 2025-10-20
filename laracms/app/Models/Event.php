<?php

namespace Lara\App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lara\Common\Models\BaseModel;

use Lara\Common\Casts\DateCast;
use Lara\Common\Casts\DateTimeCast;
use Lara\Common\Casts\TimeCast;

class Event extends BaseModel
{
    protected $table = 'lara_content_events';

	protected array $appendCasts = [
		'test_date' => DateTimeCast::class,
		'start_date' => DateCast::class,
		'start_time' => TimeCast::class,
		'end_date' => DateCast::class,
		'end_time' => TimeCast::class,
	];

	public function __construct($attributes = [])
	{
		$this->mergeCasts($this->appendCasts);
		parent::__construct($attributes);
	}

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

	public function scopeFront($query) {
		return $query->where('startdate', '>=', Carbon::today());
	}

}
