<?php

namespace Lara\App\Filament\Resources\Cities;

use Lara\Admin\Resources\Base\BaseResource;
use Lara\App\Models\City;

class CityResource extends BaseResource
{
	protected static ?string $model = City::class;

	protected static bool $shouldRegisterNavigation = false;

	public static function getPages(): array
	{
		return [
			'index'   => Pages\ListCities::route('/'),
			'create'  => Pages\CreateCity::route('/create'),
			'reorder' => Pages\ReorderCities::route('/reorder'),
			'view'    => Pages\ViewCity::route('/{record}'),
			'edit'    => Pages\EditCity::route('/{record}/edit'),
		];
	}

}
