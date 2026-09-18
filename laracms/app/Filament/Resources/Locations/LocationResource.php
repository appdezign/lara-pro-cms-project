<?php

namespace Lara\App\Filament\Resources\Locations;

use Lara\Admin\Resources\Base\BaseResource;
use Lara\App\Models\Location;

class LocationResource extends BaseResource
{
	protected static ?string $model = Location::class;

	protected static bool $shouldRegisterNavigation = true;

	public static function getPages(): array
	{
		return [
			'index'   => Pages\ListLocations::route('/'),
			'create'  => Pages\CreateLocation::route('/create'),
			'reorder' => Pages\ReorderLocations::route('/reorder'),
			'view'    => Pages\ViewLocation::route('/{record}'),
			'edit'    => Pages\EditLocation::route('/{record}/edit'),
		];
	}

}
