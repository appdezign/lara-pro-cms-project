<?php

namespace Lara\App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

use Lara\App\Database\Factories\LocationFactory;
use Lara\Common\Models\BaseModel;
use Lara\Common\Http\Concerns\HasLanguage;

class Location extends BaseModel
{
	use HasLanguage;

	protected $table = 'lara_content_locations';

	protected static function newFactory()
	{
		return LocationFactory::new();
	}

	public function teams(): HasMany
	{
		return $this->hasMany(Team::class, 'location_id');
	}

	public function events(): HasMany
	{
		return $this->hasMany(Event::class, 'location_id');
	}

}
