<?php

namespace Lara\App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Lara\App\Database\Factories\TeamFactory;

use Lara\Common\Models\BaseModel;
use Lara\Common\Http\Concerns\HasLanguage;

class Team extends BaseModel
{
	use HasLanguage;

	protected $table = 'lara_content_teams';

	protected static function newFactory()
	{
		return TeamFactory::new();
	}

	public function location(): BelongsTo
	{
		return $this->belongsTo(Location::class, 'location_id');
	}

}
