<?php

namespace Lara\App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Lara\App\Database\Factories\EventFactory;
use Lara\Common\Models\BaseModel;
use Lara\Common\Http\Concerns\HasLanguage;

use Lara\Common\Casts\DateCast;
use Lara\Common\Casts\TimeCast;

use Carbon\Carbon;

class Event extends BaseModel
{
	use HasLanguage;

	protected $table = 'lara_content_events';

	protected static function newFactory()
	{
		return EventFactory::new();
	}

	protected function casts(): array
	{
		return array_merge(parent::casts(), [
			'startdate' => DateCast::class,
			'starttime' => TimeCast::class,
			'enddate'   => DateCast::class,
			'endtime'   => TimeCast::class,
		]);
	}

	public function location(): BelongsTo
	{
		return $this->belongsTo(Location::class, 'location_id');
	}

	public function scopeFront(Builder $query): Builder
	{
		return $query->where('startdate', '>=', Carbon::today());
	}

}
