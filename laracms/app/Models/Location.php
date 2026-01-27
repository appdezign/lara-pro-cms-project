<?php

namespace Lara\App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

use Lara\Common\Models\BaseModel;
use Lara\Common\Http\Concerns\HasLanguage;

class Location extends BaseModel
{
	use HasLanguage;

	protected $table = 'lara_content_locations';

	public function teams(): HasMany
	{
		return $this->hasMany(Team::class, 'location_id');
	}

	public function events(): HasMany
	{
		return $this->hasMany(Event::class, 'location_id');
	}

}
